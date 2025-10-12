<?php
session_start();
if (!isset($_SESSION['usuario_id'])) header("Location: Inicio.html");

$usuario_id = $_SESSION['usuario_id'];
$usuario_correo = $_SESSION['usuario_correo'];

// --- Obtener tema y personalizaciones del usuario ---
$applied_theme = 'dark';
$applied_color = '#238636';
$bg_url = null;
$btn_color = '#238636';
$header_color = '#161b22';
$text_color = '#c9d1d9';

$conn_theme = new mysqli("localhost", "root", "", "promanage");
if (!$conn_theme->connect_error) {
    $stmt_t = $conn_theme->prepare("SELECT theme, custom_color, bg_url, btn_color, header_color, text_color FROM usuarios WHERE id=? LIMIT 1");
    if ($stmt_t) {
        $stmt_t->bind_param("i",$usuario_id);
        $stmt_t->execute();
        $res_t = $stmt_t->get_result()->fetch_assoc();
        if ($res_t) {
            $applied_theme = in_array($res_t['theme'], ['dark','light','custom']) ? $res_t['theme'] : 'dark';
            $applied_color = preg_match('/^#[0-9A-Fa-f]{6}$/', $res_t['custom_color'] ?? '') ? $res_t['custom_color'] : $applied_color;
            $bg_url = !empty($res_t['bg_url']) ? $res_t['bg_url'] : null;
            $btn_color = preg_match('/^#[0-9A-Fa-f]{6}$/', $res_t['btn_color'] ?? '') ? $res_t['btn_color'] : $btn_color;
            $header_color = preg_match('/^#[0-9A-Fa-f]{6}$/', $res_t['header_color'] ?? '') ? $res_t['header_color'] : $header_color;
            $text_color = preg_match('/^#[0-9A-Fa-f]{6}$/', $res_t['text_color'] ?? '') ? $res_t['text_color'] : $text_color;
        }
        $stmt_t->close();
    }
}
$conn_theme->close();

// --- Obtener proyectos del usuario ---
$conn = new mysqli("localhost", "root", "", "promanage");
if ($conn->connect_error) die("Conexión fallida: " . $conn->connect_error);

$stmt = $conn->prepare("SELECT id, nombre, descripcion, fecha_creacion, ultimo_commit FROM proyectos WHERE usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$proyectos = [];
while ($row = $result->fetch_assoc()) $proyectos[] = $row;
$stmt->close();

// --- Calcular avance por proyecto ---
$avance_proyectos = [];
foreach($proyectos as $proyecto){
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total, 
               SUM(CASE WHEN fecha_fin IS NOT NULL AND fecha_fin<>'' THEN 1 ELSE 0 END) as completadas 
        FROM actividades WHERE proyecto_id = ?");
    $stmt->bind_param("i", $proyecto['id']);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $total = (int)($res['total'] ?? 0);
    $completadas = (int)($res['completadas'] ?? 0);
    $pendientes = $total - $completadas;
    $avance_proyectos[] = [
        'id' => $proyecto['id'],
        'nombre' => $proyecto['nombre'],
        'completadas' => $completadas,
        'pendientes' => $pendientes,
        'descripcion' => $proyecto['descripcion'],
        'fecha_creacion' => $proyecto['fecha_creacion'],
        'ultimo_commit' => $proyecto['ultimo_commit']
    ];
    $stmt->close();
}

// --- Avances generales ---
$hoy = date('Y-m-d');
$fecha_inicio_semana = date('Y-m-d', strtotime('monday this week'));
$fecha_inicio_semestre = date('Y-m-d', strtotime('-6 months'));

$avance_dia = 0;
$avance_semana = 0;
$avance_semestre = 0;

$stmt = $conn->prepare("
    SELECT a.fecha_inicio, a.fecha_fin
    FROM actividades a
    JOIN proyectos p ON a.proyecto_id = p.id
    WHERE p.usuario_id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $fecha_ini = $row['fecha_inicio'];
    $fecha_fin = $row['fecha_fin'] ?: $fecha_ini;

    if($fecha_ini <= $hoy && $fecha_fin >= $hoy) $avance_dia++;
    if($fecha_ini >= $fecha_inicio_semana && $fecha_ini <= $hoy) $avance_semana++;
    if($fecha_ini >= $fecha_inicio_semestre && $fecha_ini <= $hoy) $avance_semestre++;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
 <link rel="shortcut icon" href="../IMAGEN/ChatGPT Image 24 sept 2025, 12_40_01 p.m..png" type="image/x-icon">
<title>Dashboard de <?php echo htmlspecialchars($usuario_correo); ?></title>

<link rel="stylesheet" href="../CSS/repositorio.css">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<style>
:root{
  --bg: #0d1117; --surface: #161b22; --text: #c9d1d9; --accent: #238636; --header: #161b22; --muted: #8b949e;
}
body.theme-light { --bg: #ffffff; --surface: #f6f8fa; --text: #0b0b0b; --accent: #0b5cff; --header: #e6eefc; --muted: #6b7280; }
body.theme-custom { --bg: #ffffff; --surface: #f6f8fa; --text: #0b0b0b; --muted: #6b7280; }

body {
  background-color: var(--bg);
  color: var(--text);
  margin:0; padding:0;
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  position: relative;
}

#overlay {
  position: absolute;
  inset:0;
  background-color: rgba(0, 0, 0, 0.14);
  pointer-events: none;
}

.header { background: var(--header); color: var(--text); display:flex; justify-content: space-between; align-items:center; padding:10px 20px; }
.header-left h1 { margin:0; font-size:1.5em; }
.header-left span { color: var(--muted); }
.header-right a { margin-left: 10px; text-decoration:none; color:var(--text); padding:6px 12px; border-radius:5px; }
.container { max-width:1000px; margin:20px auto; padding:10px; }
.chart-container { width:350px; margin:20px auto; text-align:center; }
#calendar { max-width:900px; margin:40px auto; background-color: var(--surface); border-radius:10px; padding:10px; color:var(--text); height:600px; z-index:1; position:relative; }
.repositorio { background:var(--surface); padding:15px; margin:15px 0; border-radius:10px; }
.repo-actions a { margin-right:10px; text-decoration:none; padding:5px 10px; border-radius:5px; background: var(--accent); color:#fff; }
.btn { background: var(--accent); color: #fff; border: none; }
.btn:hover { filter: brightness(1.05); }
</style>
</head>

<body class="<?php echo 'theme-'.htmlspecialchars($applied_theme); ?>"
      <?php
        $inline = '';
        if ($applied_theme === 'custom') {
            $inline .= "--accent: ".htmlspecialchars($btn_color).";";
            $inline .= "--header: ".htmlspecialchars($header_color).";";
            $inline .= "--text: ".htmlspecialchars($text_color).";";
        } else {
            if (!empty($btn_color)) $inline .= "--accent: ".htmlspecialchars($btn_color).";";
            if (!empty($header_color)) $inline .= "--header: ".htmlspecialchars($header_color).";";
            if (!empty($text_color)) $inline .= "--text: ".htmlspecialchars($text_color).";";
        }
        if (!empty($bg_url)) $inline .= "background-image:url('".htmlspecialchars($bg_url)."');";
        if ($inline) echo ' style="'.$inline.'"';
      ?>>
<div id="overlay"></div>

<header class="header">
    <div class="header-left">
        <h1><?php echo htmlspecialchars($usuario_correo); ?></h1>
        <span>Dashboard</span>
    </div>
    <div class="header-right">
        <a href="../HTML/crear_proyecto.php">Crear Proyecto</a>
        <a href="../HTML/perfil.php">Perfil</a>
        <a href="../HTML/Inicio.html">Cerrar sesión</a>
    </div>
</header>


<div class="container">
    <h2>Rendimiento de tareas</h2>
    <div class="chart-container">
        <canvas id="avanceTareas"></canvas>
        <p>Hoy: <?php echo $avance_dia; ?> | Semana: <?php echo $avance_semana; ?> | Últimos 6 meses: <?php echo $avance_semestre; ?></p>
    </div>

    <h2>Avance por proyecto</h2>
    <?php foreach($avance_proyectos as $i => $avance): ?>
        <div class="chart-container">
            <canvas id="proyecto_<?php echo $i; ?>"></canvas>
            <p><?php echo htmlspecialchars($avance['nombre']); ?>: Completadas <?php echo $avance['completadas']; ?> / Pendientes <?php echo $avance['pendientes']; ?></p>
        </div>
    <?php endforeach; ?>

    <h2>Calendario de Actividades</h2>
    <div id="calendar"></div>

    <h2>Mis Repositorios</h2>
    <?php if(count($proyectos) === 0): ?>
        <p>No tienes proyectos todavía. ¡Crea uno!</p>
    <?php else: ?>
        <?php foreach($avance_proyectos as $repo): ?>

            <?php
            // Conexión local para obtener maestro e integrantes (no modifica otras conexiones)
            $conn_repo = new mysqli("localhost", "root", "", "promanage");
            if ($conn_repo->connect_error) {
                $maestro_nombre = 'Error al cargar maestro';
                $integrantes = [];
            } else {
                // Obtener maestro asignado al proyecto
                $maestro_nombre = 'Sin maestro asignado';
                $stmtM = $conn_repo->prepare("
                    SELECT m.id, m.nombre
                    FROM maestros m
                    JOIN proyectos p ON p.maestro_id = m.id
                    WHERE p.id = ? LIMIT 1
                ");
                if ($stmtM) {
                    $stmtM->bind_param("i", $repo['id']);
                    $stmtM->execute();
                    $resM = $stmtM->get_result();
                    if ($rowM = $resM->fetch_assoc()) {
                        $maestro_nombre = $rowM['nombre'];
                    }
                    $stmtM->close();
                }

                // Obtener integrantes del proyecto (nombre + correo)
                $integrantes = [];
                $stmtI = $conn_repo->prepare("
                    SELECT u.id, u.nombre, u.correo
                    FROM integrantes i
                    JOIN usuarios u ON i.usuario_id = u.id
                    WHERE i.proyecto_id = ?
                    ORDER BY u.nombre ASC
                ");
                if ($stmtI) {
                    $stmtI->bind_param("i", $repo['id']);
                    $stmtI->execute();
                    $resI = $stmtI->get_result();
                    while ($u = $resI->fetch_assoc()) {
                        $integrantes[] = $u;
                    }
                    $stmtI->close();
                }
            }
            ?>

            <div class="repositorio">
                <h3><?php echo htmlspecialchars($repo['nombre']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($repo['descripcion'])); ?></p>
                <p><strong>Creado:</strong> <?php echo htmlspecialchars($repo['fecha_creacion']); ?></p>
                <p><strong>Último commit:</strong> <?php echo htmlspecialchars($repo['ultimo_commit']); ?></p>

                <p><strong>Maestro:</strong>
                    <?php if (!empty($maestro_nombre)): ?>
                        <?php echo htmlspecialchars($maestro_nombre); ?>
                    <?php else: ?>
                        <em>Sin maestro asignado</em>
                    <?php endif; ?>
                </p>

                <p><strong>Integrantes (<?php echo count($integrantes); ?>):</strong></p>
                <?php if (count($integrantes) === 0): ?>
                    <p><em>No hay integrantes agregados a este proyecto.</em></p>
                <?php else: ?>
                    <ul>
                        <?php foreach($integrantes as $int): ?>
                            <li>
                                <?php echo htmlspecialchars($int['nombre']); ?>
                                <?php if (!empty($int['correo'])): ?> — <small><?php echo htmlspecialchars($int['correo']); ?></small><?php endif; ?>
                                <?php if ((int)$int['id'] === (int)$_SESSION['usuario_id']): ?> <strong>(tú)</strong><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <div class="repo-actions">
                    <a href="ver_proyecto.php?id=<?php echo $repo['id']; ?>">Ver</a>
                    <a href="editar_proyecto.php?id=<?php echo $repo['id']; ?>">Editar</a>
                    <a href="eliminar_proyecto.php?id=<?php echo $repo['id']; ?>">Eliminar</a>
                </div>
            </div>

            <?php if (isset($conn_repo) && !$conn_repo->connect_error) $conn_repo->close(); ?>

        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal -->
<div class="modal fade" id="eventoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="eventoForm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Agregar/Editar Actividad</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
              <input type="hidden" id="eventoId">
              <div class="mb-3">
                  <label for="tituloEvento" class="form-label">Título</label>
                  <input type="text" class="form-control" id="tituloEvento" required>
              </div>
              <div class="mb-3">
                  <label for="miembroEvento" class="form-label">Numero del intregrante</label>
                  <input type="text" class="form-control" id="miembroEvento" required>
              </div>
              <div class="mb-3">
                  <label for="proyectoEvento" class="form-label">Proyecto</label>
                  <select class="form-control" id="proyectoEvento" required>
                    <?php foreach($proyectos as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                    <?php endforeach; ?>
                  </select>
              </div>
              <div class="mb-3">
                  <label for="fechaInicio" class="form-label">Fecha inicio</label>
                  <input type="date" class="form-control" id="fechaInicio" required>
              </div>
              <div class="mb-3">
                  <label for="fechaFin" class="form-label">Fecha fin</label>
                  <input type="date" class="form-control" id="fechaFin">
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" id="btnEliminar" class="btn btn-danger me-auto">Eliminar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Guardar</button>
          </div>
        </div>
    </form>
  </div>
</div>

<script>
// Gráficos
new Chart(document.getElementById('avanceTareas'), {
    type: 'doughnut',
    data: {
        labels: ['Hoy', 'Esta Semana', 'Últimos 6 Meses'],
        datasets: [{ data: [<?php echo $avance_dia; ?>, <?php echo $avance_semana; ?>, <?php echo $avance_semestre; ?>],
                     backgroundColor: ['#238636','#2ea043','#58a6ff'] }]
    },
    options: { responsive:true, plugins:{legend:{position:'bottom'}, title:{display:true,text:'Rendimiento de tareas'}} }
});

<?php foreach($avance_proyectos as $i => $avance): ?>
new Chart(document.getElementById('proyecto_<?php echo $i; ?>'), {
    type: 'doughnut',
    data: { labels: ['Completadas', 'Pendientes'], datasets: [{ data: [<?php echo $avance['completadas']; ?>, <?php echo $avance['pendientes']; ?>], backgroundColor: ['#238636','#d73a49'] }] },
    options: { responsive:true, plugins:{legend:{position:'bottom'}, title:{display:true,text:'<?php echo addslashes($avance['nombre']); ?>'}} }
});
<?php endforeach; ?>

// FullCalendar
document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('eventoModal'));
    var eventoForm = document.getElementById('eventoForm');
    var eventoId = document.getElementById('eventoId');
    var tituloEvento = document.getElementById('tituloEvento');
    var miembroEvento = document.getElementById('miembroEvento');
    var proyectoEvento = document.getElementById('proyectoEvento');
    var fechaInicio = document.getElementById('fechaInicio');
    var fechaFin = document.getElementById('fechaFin');
    var btnEliminar = document.getElementById('btnEliminar');

    var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        selectable: true,
        editable: true,
        events: '/Promanagen/HTML/actividades.php',
        select: function(info){
            eventoId.value = '';
            tituloEvento.value = '';
            miembroEvento.value = '';
            proyectoEvento.value = '<?php echo $proyectos[0]['id'] ?? 0; ?>';
            fechaInicio.value = info.startStr;
            // fecha fin inclusive
            let endDate = new Date(info.end);
            endDate.setDate(endDate.getDate() - 1);
            fechaFin.value = endDate.toISOString().split('T')[0];
            btnEliminar.style.display = 'none';
            modal.show();
        },
        eventClick: function(info){
            eventoId.value = info.event.id;
            tituloEvento.value = info.event.title;
            miembroEvento.value = (info.event.extendedProps && info.event.extendedProps.miembro) ? info.event.extendedProps.miembro : '';
            proyectoEvento.value = (info.event.extendedProps && info.event.extendedProps.proyecto_id) ? info.event.extendedProps.proyecto_id : '<?php echo $proyectos[0]['id'] ?? 0; ?>';
            fechaInicio.value = info.event.startStr;
            let endDate = info.event.end ? new Date(info.event.end) : new Date(info.event.start);
            endDate.setDate(endDate.getDate() - 1);
            fechaFin.value = endDate.toISOString().split('T')[0];
            btnEliminar.style.display = 'inline-block';
            modal.show();
        },
        eventDrop: function(info){
            let endDate = info.event.end ? new Date(info.event.end) : new Date(info.event.start);
            endDate.setDate(endDate.getDate() - 1);
            fetch('/Promanagen/HTML/editar_actividad.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:`id=${info.event.id}&fecha_inicio=${info.event.startStr}&fecha_fin=${endDate.toISOString().split('T')[0]}`
            }).then(res=>res.json())
              .then(data=>{ if(!data || data.status!=='ok'){ alert('Error al actualizar fechas'); info.revert(); } })
              .catch(()=>{ alert('Error de conexión'); info.revert(); });
        }
    });
    calendar.render();

    eventoForm.addEventListener('submit', function(e){
        e.preventDefault();
        let id = eventoId.value;
        let titulo = tituloEvento.value.trim();
        let miembro = miembroEvento.value.trim();
        let proyecto = proyectoEvento.value;
        let start = fechaInicio.value;
        let end = fechaFin.value || start;

        if(!titulo || !miembro || !start){
            alert('Completa título, asignado y fecha inicio');
            return;
        }

        let url = id ? '/Promanagen/HTML/editar_actividad.php' : '/Promanagen/HTML/agregar_actividad.php';
        let body = id
            ? `id=${encodeURIComponent(id)}&titulo=${encodeURIComponent(titulo)}&miembro=${encodeURIComponent(miembro)}&fecha_inicio=${encodeURIComponent(start)}&fecha_fin=${encodeURIComponent(end)}&proyecto_id=${encodeURIComponent(proyecto)}`
            : `titulo=${encodeURIComponent(titulo)}&miembro=${encodeURIComponent(miembro)}&fecha_inicio=${encodeURIComponent(start)}&fecha_fin=${encodeURIComponent(end)}&proyecto_id=${encodeURIComponent(proyecto)}`;

        fetch(url, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body })
          .then(res=>res.json())
          .then(data=>{
              if(data && data.status==='ok'){
                  if(id){
                      let event = calendar.getEventById(id);
                      if(event){
                          event.setProp('title', titulo);
                          event.setExtendedProp('miembro', miembro);
                          event.setExtendedProp('proyecto_id', proyecto);
                          event.setStart(data.start || start);
                          event.setEnd(data.end || end);
                      }
                  } else {
                      calendar.addEvent({ id: data.id, title: data.title, start: data.start, end: data.end, extendedProps:{miembro:miembro, proyecto_id:proyecto} });
                  }
                  modal.hide();
              } else { alert('Error al guardar actividad: ' + (data && data.msg ? data.msg : 'Verifica los datos')); }
          }).catch(err=>{ console.error(err); alert('Error de conexión al guardar actividad'); });
    });

    btnEliminar.addEventListener('click', function(){
        if(!eventoId.value) return;
        if(!confirm('¿Eliminar esta actividad?')) return;
        fetch('/Promanagen/HTML/eliminar_actividad.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`id=${encodeURIComponent(eventoId.value)}` })
          .then(res=>res.json())
          .then(data=>{
              if(data && data.status==='ok'){
                  let event = calendar.getEventById(eventoId.value);
                  if(event) event.remove();
                  modal.hide();
              } else alert('Error al eliminar actividad');
          }).catch(()=>{ alert('Error de conexión al eliminar'); });
    });
});
</script>
</body>
</html>

