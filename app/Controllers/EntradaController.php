<?php
declare(strict_types=1);

use GoolPass\Models\Entrada;
use GoolPass\Models\Partido;

final class EntradaController
{
    private Entrada $entradas; private Partido $partidos;
    public function __construct(PDO $db){$this->entradas=new Entrada($db);$this->partidos=new Partido($db);}
    public function index():void
    {
        $id=$this->id($_GET['partido_id']??null);$match=$id?$this->partidos->buscarPorId($id):null;
        if($match){$match['disponibles']=$this->partidos->aforoDisponible($id);$match['nombre_sesion']=$_SESSION['user']['nombre']??'';$match['email_sesion']=$_SESSION['user']['email']??'';}
        $canViewSales=!empty($_SESSION['user']['id']);
        show_view('entradas',['title'=>'Entradas','currentRoute'=>'entradas','partido'=>$match,'partidosDisponibles'=>$this->partidosDisponibles(),'canViewSales'=>$canViewSales,'errors'=>[],'success'=>null]);
    }
    public function buy():void
    {
        check_csrf();$matchId=$this->id($_POST['partido_id']??null);$match=$matchId?$this->partidos->buscarPorId($matchId):null;
        $errors=[];$name=trim((string)($_POST['nombre_comprador']??''));$email=mb_strtolower(trim((string)($_POST['email_comprador']??'')));
        // Una llamada representa una entrada; el modelo reserva una plaza bajo transacción.
        $quantity=filter_var($_POST['cantidad']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>1]]);
        if(!$match)$errors[]='No se encontró el partido.';
        if($quantity===false)$errors[]='Compra una entrada por operación.';
        $userId=isset($_SESSION['user']['id'])?(int)$_SESSION['user']['id']:null;
        if($userId===null){if($name===''||mb_strlen($name)>120)$errors[]='El nombre es obligatorio para una compra como invitado.';if(!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>254)$errors[]='Ingresa un correo válido para la compra como invitado.';}
        if($errors){$this->showPurchase($matchId,$errors);return;}
        try{$code='GP-'.strtoupper(bin2hex(random_bytes(8)));$this->entradas->comprar($matchId,$userId,$userId===null?$name:null,$userId===null?$email:null,$code);$this->showPurchase($matchId,[], 'La entrada se registró. Código: '.$code);}
        catch(InvalidArgumentException $e){$this->showPurchase($matchId,[$e->getMessage()]);}
        catch(Throwable $e){error_log('GoolPass compra: '.$e->getMessage());$this->showPurchase($matchId,['No se pudo completar la compra. Comprueba el aforo e inténtalo de nuevo.']);}
    }
    private function showPurchase(?int $id,array $errors,?string $success=null):void
    {
        $match=$id?$this->partidos->buscarPorId($id):null;
        if($match){$match['disponibles']=$this->partidos->aforoDisponible($id);$match['nombre_sesion']=$_SESSION['user']['nombre']??'';$match['email_sesion']=$_SESSION['user']['email']??'';}
        $canViewSales=!empty($_SESSION['user']['id']);
        show_view('entradas',['title'=>'Entradas','currentRoute'=>'entradas','partido'=>$match,'partidosDisponibles'=>$this->partidosDisponibles(),'canViewSales'=>$canViewSales,'errors'=>$errors,'success'=>$success]);
    }
    public function sales():void
    {
        need_login();
        show_view('registro_entradas',['title'=>'Entradas registradas','currentRoute'=>'entradas','compras'=>$this->entradas->listarTodas()]);
    }
    private function partidosDisponibles():array
    {
        $matches=$this->partidos->listar();
        foreach($matches as &$match){$match['disponibles']=$this->partidos->aforoDisponible((int)$match['id']);}
        unset($match);
        return $matches;
    }
    private function id(mixed $v):?int{$i=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $i===false?null:$i;}
}
