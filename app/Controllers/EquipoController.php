<?php
declare(strict_types=1);

use GoolPass\Models\Equipo;
use GoolPass\Models\Jugador;
use GoolPass\Models\Inscripcion;
use GoolPass\Models\Torneo;

final class EquipoController
{
    private Equipo $equipos; private Jugador $jugadores; private Torneo $torneos; private Inscripcion $inscripciones;
    public function __construct(PDO $db) { $this->equipos = new Equipo($db); $this->jugadores = new Jugador($db); $this->torneos = new Torneo($db); $this->inscripciones = new Inscripcion($db); }
    public function index(): void { need_login(); show_view('equipos', ['title'=>'Equipos','currentRoute'=>'equipos']+$this->datosPorOrigen()+['errors'=>[]]); }
    public function show(): void
    {
        need_login(); $id=$this->id($_GET['id']??null); $team=$id ? $this->equipos->buscarPorId($id) : null;
        if (!$team) { http_response_code(404); show_view('equipos',['title'=>'Equipos','currentRoute'=>'equipos']+$this->datosPorOrigen()+['errors'=>['No se encontró el equipo.']]); return; }
        $this->renderEquipo($team,[],null);
    }
    public function enroll(): void
    {
        need_login(); check_csrf();
        $teamId=$this->id($_POST['equipo_id']??null); $tournamentId=$this->id($_POST['torneo_id']??null);
        $team=$teamId?$this->equipos->buscarPorId($teamId):null; $tournament=$tournamentId?$this->torneos->buscarPorId($tournamentId):null; $errors=[];
        if(!$team){go_to('equipos');return;}
        if(!$tournament)$errors[]='Selecciona un torneo existente.';
        elseif(strtolower((string)($tournament['estado']??''))!=='abierto')$errors[]='El torneo no está abierto para inscripciones.';
        if($tournament&&!$this->compatible((string)$team['origen'],(string)$tournament['categoria']))$errors[]='El origen del equipo no es compatible con la categoría del torneo.';
        if($this->inscripciones->listarPorEquipo($teamId))$errors[]='Este equipo ya está inscrito en un torneo.';
        if($errors){$this->renderEquipo($team,$errors,$tournamentId);return;}
        try{$this->inscripciones->inscribir($tournamentId,$teamId);go_to('equipos/ver',['id'=>$teamId]);}
        catch(Throwable $e){error_log('GoolPass inscripción desde equipo: '.$e->getMessage());$this->renderEquipo($team,['No se pudo inscribir el equipo. Comprueba si ya tiene una inscripción.'],$tournamentId);}
    }
    public function create(): void
    {
        need_login();
        if(strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'))==='GET'){
            show_view('equipo_form',['title'=>'Crear equipo','currentRoute'=>'equipos','errors'=>[],'datos'=>[],'ligasPorOrigen'=>$this->ligasPorOrigen()]);return;
        }
        check_csrf(); $origen=(string)($_POST['origen']??''); $ligaSeleccionada=trim((string)($_POST['liga']??'')); $ligaOtro=trim((string)($_POST['liga_otro']??''));
        $d=['nombre'=>trim((string)($_POST['nombre']??'')),'liga'=>$ligaSeleccionada==='otra'?$ligaOtro:$ligaSeleccionada,'ligaSeleccionada'=>$ligaSeleccionada,'ligaOtro'=>$ligaOtro,'origen'=>$origen,'escudo'=>null]; $err=[];
        if($d['nombre']===''||mb_strlen($d['nombre'])>120)$err[]='El nombre es obligatorio y admite hasta 120 caracteres.';
        if($d['liga']===''||mb_strlen($d['liga'])>120)$err[]='La liga es obligatoria y admite hasta 120 caracteres.';
        if(in_array($d['origen'],['nacional','internacional'],true)){
            $ligas=$this->ligasPorOrigen()[$d['origen']];
            if($ligaSeleccionada!=='otra'&&!in_array($ligaSeleccionada,$ligas,true))$err[]='Selecciona una liga válida para el origen del equipo.';
            if($ligaSeleccionada==='otra'&&$ligaOtro==='')$err[]='Especifica el nombre de la liga.';
        }
        if(!in_array($d['origen'],['nacional','internacional'],true))$err[]='Selecciona un origen válido.';
        // uploadCrest valida MIME/tamaño y guarda un nombre aleatorio en public/uploads.
        if(!$err){try{$d['escudo']=$this->uploadCrest();}catch(Throwable $e){$err[]=$e->getMessage();}}
        if($err){show_view('equipo_form',['title'=>'Crear equipo','currentRoute'=>'equipos','errors'=>$err,'datos'=>$d,'ligasPorOrigen'=>$this->ligasPorOrigen()]);return;}
        try{$this->equipos->crear($d);go_to('equipos');}catch(Throwable $e){error_log('GoolPass equipo: '.$e->getMessage());if($d['escudo']&&defined('GOOLPASS_UPLOADS_DIR')){ $created=GOOLPASS_UPLOADS_DIR.DIRECTORY_SEPARATOR.basename($d['escudo']); if(is_file($created))unlink($created); }show_view('equipo_form',['title'=>'Crear equipo','currentRoute'=>'equipos','errors'=>['No se pudo guardar el equipo.'],'datos'=>$d,'ligasPorOrigen'=>$this->ligasPorOrigen()]);}
    }
    public function createPlayer(): void
    {
        need_login(); check_csrf(); $teamId=$this->id($_POST['equipo_id']??$_GET['id']??null); $name=trim((string)($_POST['nombre']??'')); $position=trim((string)($_POST['posicion']??'')); $errors=[];
        $team=$teamId?$this->equipos->buscarPorId($teamId):null;
        if(!$team)$errors[]='No se encontró el equipo.';
        if($name===''||mb_strlen($name)>120)$errors[]='El nombre del jugador es obligatorio y admite hasta 120 caracteres.';
        if($position===''||mb_strlen($position)>60)$errors[]='La posición es obligatoria y admite hasta 60 caracteres.';
        if($errors){http_response_code($team?422:404);if($team){$this->renderEquipo($team,$errors,null);}else{show_view('equipos',['title'=>'Equipos','currentRoute'=>'equipos']+$this->datosPorOrigen()+['errors'=>$errors]);}return;}
        try{$this->jugadores->crear(['equipo_id'=>$teamId,'nombre'=>$name,'posicion'=>$position]);go_to('equipos/ver',['id'=>$teamId]);}catch(Throwable $e){error_log('GoolPass jugador: '.$e->getMessage());$this->renderEquipo($team,['No se pudo guardar el jugador.'],null);}
    }
    private function uploadCrest(): ?string
    {
        $file=$_FILES['escudo']??null;
        if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return null;
        if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK||($file['size']??0)>2*1024*1024)throw new InvalidArgumentException('El escudo supera el tamaño permitido o no se pudo cargar.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
        $ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime]??null;
        if(!$ext)throw new InvalidArgumentException('El escudo debe ser PNG, JPG o WebP.');
        if(!defined('GOOLPASS_UPLOADS_DIR'))throw new RuntimeException('Falta configurar GOOLPASS_UPLOADS_DIR.');
        if(!is_dir(GOOLPASS_UPLOADS_DIR)&&!mkdir(GOOLPASS_UPLOADS_DIR,0755,true)&&!is_dir(GOOLPASS_UPLOADS_DIR))throw new RuntimeException('No se pudo preparar uploads.');
        $name=bin2hex(random_bytes(16)).'.'.$ext;$target=GOOLPASS_UPLOADS_DIR.DIRECTORY_SEPARATOR.$name;
        if(!move_uploaded_file((string)$file['tmp_name'],$target))throw new RuntimeException('No se pudo guardar el escudo.');
        return 'uploads/'.$name;
    }
    private function datosPorOrigen(): array
    {
        $groups=['nacional'=>[],'internacional'=>[]];
        foreach($this->equipos->listar() as $equipo){$origen=strtolower((string)($equipo['origen']??''));if(isset($groups[$origen]))$groups[$origen][]=$equipo;}
        return ['equiposNacionales'=>$groups['nacional'],'equiposInternacionales'=>$groups['internacional']];
    }
    private function renderEquipo(array $team,array $errors,?int $selectedTournamentId): void
    {
        $inscripcion=$this->inscripciones->listarPorEquipo((int)$team['id']); $torneosDisponibles=[];
        if(!$inscripcion){
            foreach($this->torneos->listar() as $torneo){
                if(strtolower((string)($torneo['estado']??''))==='abierto'&&(int)($torneo['inscripciones_abiertas']??1)===1&&$this->compatible((string)$team['origen'],(string)$torneo['categoria']))$torneosDisponibles[]=$torneo;
            }
        }
        show_view('equipo',['title'=>$team['nombre'],'currentRoute'=>'equipos','equipo'=>$team,'jugadores'=>$this->jugadores->listarPorEquipo((int)$team['id']),'inscripcion'=>$inscripcion,'torneosDisponibles'=>$torneosDisponibles,'selectedTournamentId'=>$selectedTournamentId,'errors'=>$errors]);
    }
    private function compatible(string $origen,string $categoria): bool
    {
        return $origen==='nacional'?in_array($categoria,['nacional','internacional'],true):($origen==='internacional'&&$categoria==='internacional');
    }
    private function ligasPorOrigen(): array
    {
        return [
            'nacional'=>['Liga 1 (Perú)','Liga 2 (Perú)','Liga 3 (Perú)','Copa Perú'],
            'internacional'=>['Premier League (Inglaterra)','LaLiga (España)','Serie A (Italia)','Bundesliga (Alemania)','Ligue 1 (Francia)','Primeira Liga (Portugal)','Eredivisie (Países Bajos)','MLS (Estados Unidos)','Liga MX (México)','Brasileirão (Brasil)','Primera División (Argentina)'],
        ];
    }
    private function id(mixed $value): ?int { $id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $id===false?null:$id; }
}
