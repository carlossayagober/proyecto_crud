<?php
$host="localhost";
$db="crud_php";
$user="root";
$pass="";

try{
 $conexion=new PDO("mysql:host=$host;dbname=$db;charset=utf8",$user,$pass);
 $conexion->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
 die("Error ".$e->getMessage());
}

$mensaje="";
$error="";

/* ===== MENSAJES POR URL ===== */
if(isset($_GET['msg']) && !isset($_POST['crear']) && !isset($_POST['actualizar'])){
 if($_GET['msg']=="eliminado") $mensaje="Registro eliminado";
 if($_GET['msg']=="creado") $mensaje="Perfil creado correctamente";
 if($_GET['msg']=="actualizado") $mensaje="Registro actualizado";
 if($_GET['msg']=="borrados") $mensaje="Todos los registros fueron borrados";
 if($_GET['msg']=="no_encontrado") $error="Registro no encontrado";
}

function solo_letras($t){
 return preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/u",$t);
}

/* ===== VALIDAR EMAIL CON PUNTO OBLIGATORIO ===== */
function email_valido($e){
 return filter_var($e,FILTER_VALIDATE_EMAIL) && strpos(explode('@',$e)[1] ?? '', '.') !== false;
}

/* ===== CREAR ===== */
if(isset($_POST['crear'])){
 $nombre=trim($_POST['nombre']);
 $email=trim($_POST['email']);
 $telefono=preg_replace("/[^0-9]/","",$_POST['telefono']);

 if(!solo_letras($nombre)){
  $error="El nombre solo permite letras";
 }
 elseif(!email_valido($email)){
  $error="Email inválido (debe contener @ y punto)";
 }
 elseif($telefono==""){
  $error="Teléfono obligatorio";
 }
 else{
  $c1=$conexion->prepare("SELECT COUNT(*) FROM perfiles WHERE email=?");
  $c1->execute([$email]);

  $c2=$conexion->prepare("SELECT COUNT(*) FROM perfiles WHERE telefono=?");
  $c2->execute([$telefono]);

  if($c1->fetchColumn()>0){
   $error="Ese correo ya existe";
  }
  elseif($c2->fetchColumn()>0){
   $error="Ese teléfono ya existe";
  }
  else{
   $stmt=$conexion->prepare("INSERT INTO perfiles(nombre,email,telefono) VALUES(?,?,?)");
   $stmt->execute([$nombre,$email,$telefono]);
   header("Location:index.php?msg=creado");
   exit;
  }
 }
}

/* ===== ELIMINAR UNO ===== */
if(isset($_GET['eliminar'])){
 $stmt=$conexion->prepare("DELETE FROM perfiles WHERE id=?");
 $stmt->execute([$_GET['eliminar']]);
 header("Location:index.php?msg=eliminado");
 exit;
}

/* ===== ELIMINAR TODOS ===== */
if(isset($_GET['borrar_todos'])){
 $conexion->exec("TRUNCATE TABLE perfiles");
 header("Location:index.php?msg=borrados");
 exit;
}

/* ===== OBTENER PARA EDITAR ===== */
$editarData=null;
if(isset($_GET['editar'])){
 $stmt=$conexion->prepare("SELECT * FROM perfiles WHERE id=?");
 $stmt->execute([$_GET['editar']]);
 $editarData=$stmt->fetch();
}

/* ===== ACTUALIZAR ===== */
if(isset($_POST['actualizar'])){
 $nombre=trim($_POST['nombre']);
 $email=trim($_POST['email']);
 $telefono=preg_replace("/[^0-9]/","",$_POST['telefono']);

 if(!solo_letras($nombre)){
  $error="El nombre solo permite letras";
 }
 elseif(!email_valido($email)){
  $error="Email inválido (debe contener @ y punto)";
 }
 elseif($telefono==""){
  $error="Teléfono obligatorio";
 }
 else{
  $c1=$conexion->prepare("SELECT COUNT(*) FROM perfiles WHERE email=? AND id<>?");
  $c1->execute([$email,$_POST['id']]);

  $c2=$conexion->prepare("SELECT COUNT(*) FROM perfiles WHERE telefono=? AND id<>?");
  $c2->execute([$telefono,$_POST['id']]);

  if($c1->fetchColumn()>0){
   $error="Ese correo ya existe";
  }
  elseif($c2->fetchColumn()>0){
   $error="Ese teléfono ya existe";
  }
  else{
   $stmt=$conexion->prepare("UPDATE perfiles SET nombre=?,email=?,telefono=? WHERE id=?");
   $stmt->execute([$nombre,$email,$telefono,$_POST['id']]);
   header("Location:index.php?msg=actualizado");
   exit;
  }
 }
}

/* ===== LISTADO / BUSQUEDA ===== */
$rows=[];
if(isset($_GET['buscar_id']) && $_GET['buscar_id']!=""){
 $stmt=$conexion->prepare("SELECT * FROM perfiles WHERE id=?");
 $stmt->execute([$_GET['buscar_id']]);
 $rows=$stmt->fetchAll();
 if(!$rows) header("Location:index.php?msg=no_encontrado");
} else {
 $rows=$conexion->query("SELECT * FROM perfiles ORDER BY id DESC")->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CRUD Perfiles</title>

<style>
body{font-family:Arial;background:#f4f6f9;padding:40px}
h1{text-align:center;margin-bottom:25px}
.layout{display:grid;grid-template-columns:320px 1fr;gap:25px;max-width:1100px;margin:auto}
.card{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08)}
label{font-weight:bold;margin-top:10px;display:block}
input{width:100%;padding:9px;margin-top:6px;border-radius:6px;border:1px solid #d1d5db}
button{margin-top:15px;width:100%;padding:11px;border:none;border-radius:7px;background:#2563eb;color:white;cursor:pointer}
.btn-sm{width:auto;padding:8px 14px;margin-top:0;display:inline-block;text-decoration:none;color:white;border-radius:7px;font-size:14px}
.btn-gray{background:#6b7280}
.btn-red{background:#dc2626}
.alert{margin:0 auto 20px auto;padding:12px;border-radius:8px;text-align:center;animation:fade 5s forwards;max-width:1100px}
.ok{background:#dcfce7;color:#166534}
.err{background:#fee2e2;color:#991b1b}
@keyframes fade{0%{opacity:1}85%{opacity:1}100%{opacity:0}}
.table{width:100%;border-collapse:collapse}
.table th{background:#111827;color:white;padding:12px}
.table td{padding:12px;border-bottom:1px solid #e5e7eb}
.action{padding:6px 10px;border-radius:6px;font-size:13px;text-decoration:none;color:white}
.edit{background:#0ea5e9}
.delete{background:#ef4444}
.tools{display:flex;gap:10px;align-items:center;margin-bottom:15px}
@media(max-width:900px){.layout{grid-template-columns:1fr}}
</style>
</head>

<body>

<h1>Regístrate</h1>

<?php if($mensaje && !$error): ?>
<div class="alert ok"><?= $mensaje ?></div>
<?php endif; ?>

<?php if($error): ?>
<div class="alert err"><?= $error ?></div>
<?php endif; ?>

<div class="layout">

<div class="card">
<h3><?= $editarData?"Editar":"Nuevo" ?> perfil</h3>
<form method="POST">
<input type="hidden" name="id" value="<?= $editarData['id'] ?? '' ?>">

<label>Nombre</label>
<input name="nombre" required pattern="[A-Za-záéíóúÁÉÍÓÚñÑ ]+" value="<?= $editarData['nombre'] ?? '' ?>">

<label>Email</label>
<input name="email" type="email" required pattern=".+@.+\..+" value="<?= $editarData['email'] ?? '' ?>">

<label>Teléfono</label>
<input name="telefono" required pattern="[0-9]+" value="<?= $editarData['telefono'] ?? '' ?>">

<?php if($editarData): ?>
<button name="actualizar">Actualizar</button>
<?php else: ?>
<button name="crear">Guardar</button>
<?php endif; ?>
</form>
</div>

<div class="card">
<h3>Registros guardados</h3>

<div class="tools">
<form method="GET" style="display:flex;gap:10px;">
<input type="number" name="buscar_id" placeholder="Buscar por ID" style="max-width:180px;padding:8px;border:1px solid #d1d5db;border-radius:6px;">
<button class="btn-sm">Buscar</button>
</form>

<a href="index.php" class="btn-sm btn-gray">Ver todos</a>

<a href="?borrar_todos=1" class="btn-sm btn-red" onclick="return confirm('¿Seguro de eliminar TODOS los datos?')">Borrar todos</a>
</div>

<table class="table">
<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Acciones</th></tr>

<?php foreach($rows as $f): ?>
<tr>
<td><?= $f['id'] ?></td>
<td><?= $f['nombre'] ?></td>
<td><?= $f['email'] ?></td>
<td><?= $f['telefono'] ?></td>
<td>
<a class="action edit" href="?editar=<?= $f['id'] ?>">Editar</a>
<a class="action delete" onclick="return confirm('¿Eliminar registro?')" href="?eliminar=<?= $f['id'] ?>">Eliminar</a>
</td>
</tr>
<?php endforeach; ?>

</table>
</div>

</div>
</body>
</html>
