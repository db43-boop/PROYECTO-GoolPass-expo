<?php
declare(strict_types=1);

use GoolPass\Models\Equipo;
use GoolPass\Models\Jugador;

final class EquipoController
{
    private Equipo $equipos; private Jugador $jugadores;
    public function __construct(PDO $db) { $this->equipos = new Equipo($db); $this->jugadores = new Jugador($db); }
    public function index(): void { need_login(); show_view('equipos', ['title'=>'Equipos','currentRoute'=>'equipos']+$this->datosPorOrigen()+['errors'=>[]]); }
    public function show(): void
    {
        need_login(); $id=$this->id($_GET['id']??null); $team=$id ? $this->equipos->buscarPorId($id) : null;
        if (!$team) { http_response_code(404); show_view('equipos',['title'=>'Equipos','currentRoute'=>'equipos']+$this->datosPorOrigen()+['errors'=>['No se encontró el equipo.']]); return; }
        show_view('equipo',['title'=>$team['nombre'],'currentRoute'=>'equipos','equipo'=>$team,'jugadores'=>$this->jugadores->listarPorEquipo($id),'errors'=>[]]);
    }
    public function create(): void
    {
        need_login();
        if(strtoupper((string)($_SERVER['REQUEST_METHOD']??'GET'))==='GET'){
            show_view('equipo_form',['title'=>'Crear equipo','currentRoute'=>'equipos','errors'=>[],'datos'=>[]]);return;
        }
        check_csrf(); $d=['nombre'=>trim((string)($_POST['nombre']??'')),'liga'=>trim((string)($_POST['liga']??'')),'origen'=>(string)($_POST['origen']??''),'escudo'=>null]; $err=[];
        if($d['nombre']===''||mb_strlen($d['nombre'])>120)$err[]='El nombre es obligatorio y admite hasta 120 caracteres.';
        if($d['liga']===''||mb_strlen($d['liga'])>120)$err[]='La liga es obligatoria y admite hasta 120 caracteres.';
        if(!in_array($d['origen'],['nacional','internacional'],true))$err[]='Selecciona un origen válido.';
        // uploadCrest valida MIME/tamaño y guarda un nombre aleatorio en public/uploads.
        if(!$err){try{$d['escudo']=$this->uploadCrest();}catch(Throwable $e){$err[]=$e->getMessage();}}
        if($err){show_view('equipo_form',['title'=>'Crear equipo','currentRoute'=>'equipos','errors'=>$err,'datos'=>$d]);return;}
        try{$this->equipos->crear($d);go_to('equipos');}catch(Throwable $e){error_log('GoolPass equipo: '.$e->getMessage());if($d['escudo']&&defined('GOOLPASS_UPLOADS_DIR')){ $created=GOOLPASS_UPLOADS_DIR.DIRECTORY_SEPARATOR.basename($d['escudo']); if(is_file($created))unlink($created); }show_view('equipo_form',['title'=>'Crear equipo','currentRoute'=>'equipos','errors'=>['No se pudo guardar el equipo.'],'datos'=>$d]);}
    }
    public function createPlayer(): void
    {
        need_login(); check_csrf(); $teamId=$this->id($_POST['equipo_id']??$_GET['id']??null); $name=trim((string)($_POST['nombre']??'')); $position=trim((string)($_POST['posicion']??'')); $errors=[];
        $team=$teamId?$this->equipos->buscarPorId($teamId):null;
        if(!$team)$errors[]='No se encontró el equipo.';
        if($name===''||mb_strlen($name)>120)$errors[]='El nombre del jugador es obligatorio y admite hasta 120 caracteres.';
        if($position===''||mb_strlen($position)>60)$errors[]='La posición es obligatoria y admite hasta 60 caracteres.';
        if($errors){http_response_code($team?422:404);show_view('equipo',['title'=>$team['nombre']??'Equipo','currentRoute'=>'equipos','equipo'=>$team??[],'jugadores'=>$team?$this->jugadores->listarPorEquipo($teamId):[],'errors'=>$errors]);return;}
        try{$this->jugadores->crear(['equipo_id'=>$teamId,'nombre'=>$name,'posicion'=>$position]);go_to('equipos/ver',['id'=>$teamId]);}catch(Throwable $e){error_log('GoolPass jugador: '.$e->getMessage());show_view('equipo',['title'=>$team['nombre'],'equipo'=>$team,'jugadores'=>$this->jugadores->listarPorEquipo($teamId),'errors'=>['No se pudo guardar el jugador.']]);}
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
    private function id(mixed $value): ?int { $id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $id===false?null:$id; }
}
