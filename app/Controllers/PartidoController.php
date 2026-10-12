<?php
declare(strict_types=1);

use GoolPass\Models\Equipo;
use GoolPass\Models\EstadisticaPartido;
use GoolPass\Models\Inscripcion;
use GoolPass\Models\Jugador;
use GoolPass\Models\Partido;
use GoolPass\Models\Torneo;

final class PartidoController
{
    private Equipo $equipos;private Torneo $torneos;private Partido $partidos;private Inscripcion $inscripciones;private Jugador $jugadores;private EstadisticaPartido $stats;
    public function __construct(PDO $db){$this->equipos=new Equipo($db);$this->torneos=new Torneo($db);$this->partidos=new Partido($db);$this->inscripciones=new Inscripcion($db);$this->jugadores=new Jugador($db);$this->stats=new EstadisticaPartido($db);}
    public function index():void{need_login();$this->render([],null,null,$this->id($_GET['torneo_id']??null));}
    public function create():void
    {
        need_login();check_csrf();$d=['torneo_id'=>$this->id($_POST['torneo_id']??null),'equipo_local_id'=>$this->id($_POST['equipo_local_id']??null),'equipo_visitante_id'=>$this->id($_POST['equipo_visitante_id']??null),'fecha_hora'=>trim((string)($_POST['fecha_hora']??'')),'estadio'=>trim((string)($_POST['estadio']??'')),'precio_entrada'=>$_POST['precio_entrada']??null,'aforo'=>$_POST['aforo']??null];$errors=[];
        $t=$d['torneo_id']?$this->torneos->buscarPorId($d['torneo_id']):null;
        if(!$t)$errors[]='Selecciona un torneo existente.';
        if(!$d['equipo_local_id']||!$this->equipos->buscarPorId($d['equipo_local_id']))$errors[]='Selecciona un equipo local existente.';
        if(!$d['equipo_visitante_id']||!$this->equipos->buscarPorId($d['equipo_visitante_id']))$errors[]='Selecciona un equipo visitante existente.';
        if($d['equipo_local_id']&&$d['equipo_local_id']===$d['equipo_visitante_id'])$errors[]='Los equipos local y visitante deben ser distintos.';
        if($t&&$d['equipo_local_id']&&!$this->enrolled($d['torneo_id'],$d['equipo_local_id']))$errors[]='El equipo local no está inscrito en el torneo.';
        if($t&&$d['equipo_visitante_id']&&!$this->enrolled($d['torneo_id'],$d['equipo_visitante_id']))$errors[]='El equipo visitante no está inscrito en el torneo.';
        $date=DateTimeImmutable::createFromFormat('Y-m-d\TH:i',$d['fecha_hora']);if(!$date||$date->format('Y-m-d\TH:i')!==$d['fecha_hora'])$errors[]='Ingresa una fecha y hora válidas.';
        if($d['estadio']===''||mb_strlen($d['estadio'])>140)$errors[]='El estadio es obligatorio (máximo 140 caracteres).';
        if(!is_numeric($d['precio_entrada'])||(float)$d['precio_entrada']<0)$errors[]='El precio debe ser un número igual o mayor a cero.';
        if(($aforo=filter_var($d['aforo'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]))===false)$errors[]='El aforo debe ser un entero mayor que cero.';
        if($errors){$this->render($errors,null,null,$d['torneo_id']);return;}
        $d['fecha_hora']=$date->format('Y-m-d H:i:s');$d['precio_entrada']=(float)$d['precio_entrada'];$d['aforo']=$aforo;
        try{$this->partidos->crear($d);go_to('partidos',['torneo_id'=>$d['torneo_id']]);}catch(Throwable $e){error_log('GoolPass partido: '.$e->getMessage());$this->render(['No se pudo crear el partido.'],null,null,$d['torneo_id']);}
    }
    public function statistics():void
    {
        need_login();$id=$this->id($_GET['id']??$_POST['partido_id']??null);$match=$id?$this->partidos->buscarPorId($id):null;
        if(!$match){http_response_code(404);$this->render(['No se encontró el partido.']);return;}
        $tournamentId=(int)$match['torneo_id'];
        if($_SERVER['REQUEST_METHOD']==='POST'){
            check_csrf();$rows=$_POST['estadisticas']??[];$errors=[];$mvp=0;
            if(!is_array($rows)||count($rows)>100){$this->render(['Datos de estadísticas no válidos.'],$id,is_array($rows)?$rows:null,$tournamentId);return;}
            foreach($rows as $playerId=>$row){$pid=$this->id($playerId);if(!$pid||!is_array($row)){$errors[]='Hay un jugador inválido.';continue;}
                $teamId=0;foreach([(int)$match['equipo_local_id'],(int)$match['equipo_visitante_id']] as $candidateTeam){foreach($this->jugadores->listarPorEquipo($candidateTeam) as $candidate){if((int)$candidate['id']===$pid){$teamId=$candidateTeam;break 2;}}}
                if($teamId===0){$errors[]='Solo puedes registrar estadísticas de jugadores de los equipos del partido.';continue;}
                $clean=[];foreach(['goles','asistencias','tarjetas_amarillas','tarjetas_rojas'] as $field){$value=filter_var($row[$field]??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);if($value===false){$errors[]='Las estadísticas deben ser enteros no negativos.';$value=0;}$clean[$field]=$value;}
                $clean['fue_mvp']=!empty($row['fue_mvp'])?1:0;$mvp+=$clean['fue_mvp'];$clean['jugador_id']=$pid;
                $rows[$playerId]=$clean;
            }
            if($mvp>1)$errors[]='Solo puede marcarse un MVP por partido.';
            if($errors){$this->render($errors,$id,$rows,$tournamentId);return;}
            try{foreach($rows as $row)$this->stats->guardar(['partido_id'=>$id]+$row);go_to('partidos/estadisticas',['id'=>$id,'guardado'=>1]);}catch(Throwable $e){error_log('GoolPass estadisticas: '.$e->getMessage());$this->render(['No se pudieron guardar las estadísticas.'],$id,$rows,$tournamentId);return;}
        }
        $this->render([],$id,null,$tournamentId);
    }
    private function enrolled(int $tournamentId,int $teamId):bool{$row=$this->inscripciones->listarPorEquipo($teamId);return $row&&(int)$row['torneo_id']===$tournamentId;}
    private function render(array $errors,?int $preserveMatchId=null,?array $submittedStats=null,?int $selectedTournamentId=null):void
    {
        $torneos=$this->torneos->listar();$allMatches=$this->partidos->listar();$matchCounts=[];
        foreach($allMatches as $item){$tid=(int)($item['torneo_id']??0);$matchCounts[$tid]=($matchCounts[$tid]??0)+1;}
        foreach($torneos as &$tournament){$tournament['cantidad_partidos']=$matchCounts[(int)$tournament['id']]??0;}unset($tournament);
        $selectedTournament=$selectedTournamentId?$this->torneos->buscarPorId($selectedTournamentId):null;
        $registeredRows=$selectedTournament?$this->inscripciones->listarPorTorneo((int)$selectedTournament['id']):[];
        $registeredTeams=array_map(static fn(array $row):array=>['id'=>(int)$row['equipo_id'],'nombre'=>$row['equipo_nombre']],$registeredRows);
        $matches=$selectedTournament?$this->partidos->listar((int)$selectedTournament['id']):[];$playersByMatch=[];$statsByMatch=[];
        foreach($matches as &$match){
            $match['equipo_local_nombre']=$match['equipo_local_nombre']??$match['equipo_local']??'';
            $match['equipo_visitante_nombre']=$match['equipo_visitante_nombre']??$match['equipo_visitante']??'';
            $matchId=(int)$match['id'];$playersByMatch[$matchId]=[];
            $teams=[(int)$match['equipo_local_id']=>$match['equipo_local_nombre'],(int)$match['equipo_visitante_id']=>$match['equipo_visitante_nombre']];
            foreach($teams as $teamId=>$teamName){
                foreach($this->jugadores->listarPorEquipo($teamId) as $player){
                    $playersByMatch[$matchId][]=['id'=>(int)$player['id'],'nombre'=>(string)$player['nombre'],'equipo_nombre'=>(string)$teamName];
                }
            }
            foreach($this->stats->listarPorPartido($matchId) as $stat){
                $statsByMatch[$matchId][(string)$stat['jugador_id']]=[
                    'goles'=>(int)$stat['goles'],'asistencias'=>(int)$stat['asistencias'],
                    'tarjetas_amarillas'=>(int)$stat['tarjetas_amarillas'],'tarjetas_rojas'=>(int)$stat['tarjetas_rojas'],
                    'fue_mvp'=>(int)$stat['fue_mvp'],
                ];
            }
        }
        unset($match);
        if($preserveMatchId!==null&&$submittedStats!==null){
            foreach($submittedStats as $playerId=>$row){
                if(!is_array($row))continue;
                $statsByMatch[$preserveMatchId][(string)$playerId]=[
                    'goles'=>$row['goles']??0,'asistencias'=>$row['asistencias']??0,
                    'tarjetas_amarillas'=>$row['tarjetas_amarillas']??0,'tarjetas_rojas'=>$row['tarjetas_rojas']??0,
                    'fue_mvp'=>!empty($row['fue_mvp'])?1:0,
                ];
            }
        }
        show_view('partidos',['title'=>'Partidos','currentRoute'=>'partidos','partidos'=>$matches,'torneos'=>$torneos,'torneoSeleccionado'=>$selectedTournament,'equipos'=>$registeredTeams,'jugadoresPorPartido'=>$playersByMatch,'estadisticasActuales'=>$statsByMatch,'errors'=>$errors]);
    }
    private function id(mixed $v):?int{$i=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $i===false?null:$i;}
}
