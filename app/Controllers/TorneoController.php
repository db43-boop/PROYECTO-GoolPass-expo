<?php
declare(strict_types=1);

use GoolPass\Models\Equipo;
use GoolPass\Models\Inscripcion;
use GoolPass\Models\Torneo;

final class TorneoController
{
    private Equipo $equipos; private Torneo $torneos; private Inscripcion $inscripciones;
    public function __construct(PDO $db){$this->equipos=new Equipo($db);$this->torneos=new Torneo($db);$this->inscripciones=new Inscripcion($db);}
    public function index():void
    {
        need_login();$torneos=$this->torneosConEquipos();
        show_view('torneos',['title'=>'Torneos','currentRoute'=>'torneos','torneos'=>$torneos,'errors'=>[]]);
    }
    public function show():void
    {
        need_login();$id=$this->id($_GET['id']??null);$torneo=$id?$this->torneos->buscarPorId($id):null;
        if(!$torneo){http_response_code(404);go_to('torneos');return;}
        $this->renderDetalle($torneo,[]);
    }
    public function create():void
    {
        need_login();
        if(strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'))==='GET'){
            show_view('torneo_form',['title'=>'Crear torneo','currentRoute'=>'torneos','errors'=>[],'datos'=>[]]);return;
        }
        check_csrf();$d=['nombre'=>trim((string)($_POST['nombre']??'')),'categoria'=>(string)($_POST['categoria']??''),'formato'=>(string)($_POST['formato']??''),'fecha_inicio'=>(string)($_POST['fecha_inicio']??''),'fecha_fin'=>(string)($_POST['fecha_fin']??''),'estado'=>'abierto'];$errors=[];
        if($d['nombre']===''||mb_strlen($d['nombre'])>140)$errors[]='El nombre es obligatorio y admite hasta 140 caracteres.';
        if(!in_array($d['categoria'],['nacional','internacional'],true))$errors[]='Selecciona una categoría válida.';
        if($d['formato']!==''&&!in_array($d['formato'],['liga','eliminacion','grupos','otro'],true))$errors[]='Selecciona un formato válido.';
        if($d['fecha_inicio']!==''&&!$this->date($d['fecha_inicio']))$errors[]='La fecha de inicio no es válida.';
        if($d['fecha_fin']!==''&&!$this->date($d['fecha_fin']))$errors[]='La fecha de fin no es válida.';
        if($d['fecha_inicio']&&$d['fecha_fin']&&$d['fecha_fin']<$d['fecha_inicio'])$errors[]='La fecha de fin debe ser igual o posterior al inicio.';
        if($errors){show_view('torneo_form',['title'=>'Crear torneo','currentRoute'=>'torneos','errors'=>$errors,'datos'=>$d]);return;}
        try{$this->torneos->crear($d);go_to('torneos');}catch(Throwable $e){error_log('GoolPass torneo: '.$e->getMessage());show_view('torneo_form',['title'=>'Crear torneo','currentRoute'=>'torneos','errors'=>['No se pudo crear el torneo.'],'datos'=>$d]);}
    }
    public function enroll():void
    {
        need_login();check_csrf();$tid=$this->id($_POST['torneo_id']??null);$eid=$this->id($_POST['equipo_id']??null);$t=$tid?$this->torneos->buscarPorId($tid):null;$e=$eid?$this->equipos->buscarPorId($eid):null;$errors=[];
        if(!$t)$errors[]='Selecciona un torneo existente.';elseif(strtolower((string)($t['estado']??''))!=='abierto')$errors[]='El torneo no está abierto para inscripciones.';
        if(!$e)$errors[]='Selecciona un equipo existente.';
        if($e&&$t&&!$this->eligible((string)$e['origen'],(string)$t['categoria']))$errors[]='El origen del equipo no es compatible con la categoría del torneo.';
        if($e&&$this->inscripciones->listarPorEquipo($eid))$errors[]='El equipo ya está inscrito en un torneo.';
        if($errors){if($t){$this->renderDetalle($t,$errors);}else{show_view('torneos',['title'=>'Torneos','currentRoute'=>'torneos','torneos'=>$this->torneosConEquipos(),'errors'=>$errors]);}return;}
        try{$this->inscripciones->inscribir($tid,$eid);go_to('torneos/ver',['id'=>$tid]);}catch(Throwable $e){$this->renderDetalle($t??$this->torneos->buscarPorId($tid),['No se pudo inscribir el equipo. Puede que ya tenga una inscripción.']);}
    }
    private function renderDetalle(?array $torneo,array $errors):void
    {
        if(!$torneo){go_to('torneos');return;}
        $inscritos=$this->inscripciones->listarPorTorneo((int)$torneo['id']);$disponibles=[];
        foreach($this->equipos->listar() as $equipo){
            if(!$this->inscripciones->listarPorEquipo((int)$equipo['id'])&&$this->eligible((string)$equipo['origen'],(string)$torneo['categoria']))$disponibles[]=$equipo;
        }
        show_view('torneo_detalle',['title'=>$torneo['nombre'],'currentRoute'=>'torneos','torneo'=>$torneo,'equiposInscritos'=>$inscritos,'equiposDisponibles'=>$disponibles,'errors'=>$errors]);
    }
    private function torneosConEquipos():array{$torneos=$this->torneos->listar();foreach($torneos as &$torneo){$torneo['equipos_inscritos']=$this->inscripciones->listarPorTorneo((int)$torneo['id']);}unset($torneo);return $torneos;}
    private function eligible(string $origin,string $category):bool{return $origin==='nacional'?in_array($category,['nacional','internacional'],true):($origin==='internacional'&&$category==='internacional');}
    private function date(string $value):bool{$d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);return $d&&$d->format('Y-m-d')===$value;}
    private function id(mixed $v):?int{$i=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $i===false?null:$i;}
}
