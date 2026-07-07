<?php

include('bd.php');

function obtenerGastosDiarios($conexion)
{
    // Consulta SQL mejorada: removemos "Fuente_Dinero != 'Externo'" para capturarlos, 
    // y los dividimos usando SUM(CASE WHEN...)
    $dias = "
        SELECT 
            CASE 
                WHEN DAYOFWEEK(Fecha) = 2 THEN 'Lunes'
                WHEN DAYOFWEEK(Fecha) = 3 THEN 'Martes'
                WHEN DAYOFWEEK(Fecha) = 4 THEN 'Miércoles'
                WHEN DAYOFWEEK(Fecha) = 5 THEN 'Jueves'
                WHEN DAYOFWEEK(Fecha) = 6 THEN 'Viernes'
                WHEN DAYOFWEEK(Fecha) = 7 THEN 'Sábado'
                WHEN DAYOFWEEK(Fecha) = 1 THEN 'Domingo'
            END AS nombre_dia, 
            SUM(CASE WHEN Fuente_Dinero != 'Externo' THEN Valor ELSE 0 END) AS gastos_propio,
            SUM(CASE WHEN Fuente_Dinero = 'Externo' THEN Valor ELSE 0 END) AS gastos_externo
        FROM 
            gastos 
        WHERE 
            Fecha >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
            AND Fecha < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)
            AND ID_Categoria_Gastos != 1
        GROUP BY 
            DAYOFWEEK(Fecha) 
        ORDER BY 
            FIELD(nombre_dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo');
    ";

    $datos_dias = $conexion->query($dias);

    $labels_semanales = [];
    $gastos_propio    = [];
    $gastos_externo   = [];

    while ($fila = $datos_dias->fetch_assoc()) {
        $labels_semanales[] = $fila['nombre_dia'];
        $gastos_propio[]    = (float)$fila['gastos_propio'];
        $gastos_externo[]   = (float)$fila['gastos_externo'];
    }

    // Retornamos los datos serializados listos para JavaScript
    return [
        'labels'  => json_encode($labels_semanales),
        'propio'  => json_encode($gastos_propio),
        'externo' => json_encode($gastos_externo)
    ];
}
// Ejecución y asignación de variables
$resultados_dias = obtenerGastosDiarios($conexion);
$labels_semanales = $resultados_dias['labels'] ?? '[]';
$gastos_propio    = $resultados_dias['propio'] ?? '[]';
$gastos_externo   = $resultados_dias['externo'] ?? '[]';

function obtenerGastosSemanales($conexion)
{
    // Consulta SQL mejorada para capturar el total neto propio y el total externo de cada semana
    $consulta = "
        SELECT 
            MIN(Fecha) AS Fecha, 
            YEAR(Fecha) AS anio,
            WEEK(Fecha, 1) AS semana, 
            SUM(CASE WHEN Fuente_Dinero != 'Externo' THEN Valor ELSE 0 END) AS gastos_propio,
            SUM(CASE WHEN Fuente_Dinero = 'Externo' THEN Valor ELSE 0 END) AS gastos_externo
        FROM 
            gastos
        WHERE 
            Fecha >= CURDATE() - INTERVAL 8 WEEK 
            AND ID_Categoria_Gastos != 1
        GROUP BY 
            anio, semana 
        ORDER BY anio DESC, semana DESC  
        LIMIT 8;
    ";

    // Obtener el número de la semana actual
    $semana_actual = date('W');
    $semana_menos_4 = $semana_actual - 4;

    if ($semana_menos_4 < 1) {
        $semana_menos_4 += 52;
    }

    $resultados = $conexion->query($consulta);

    // Inicializar arreglos para clasificar la data
    $gastos_semanales_actual = [];
    $gastos_semanales_anterior = [];
    $externo_semanales_actual = [];
    $externo_semanales_anterior = [];
    $labels_mensuales = [];

    $contador_actual = 0;
    $contador_anterior = 0;

    // Procesar los resultados de la consulta
    while ($fila = $resultados->fetch_assoc()) {
        $etiqueta_semana = "Semana " . $fila['semana'];
        $labels_mensuales[] = $etiqueta_semana;

        // Clasificar según el periodo (Mes Actual vs Mes Anterior)
        if ((int)$fila['semana'] > $semana_menos_4) {
            $gastos_semanales_actual[]  = (float)$fila['gastos_propio'];
            $externo_semanales_actual[] = (float)$fila['gastos_externo'];
            $contador_actual++;
        } else {
            $gastos_semanales_anterior[]  = (float)$fila['gastos_propio'];
            $externo_semanales_anterior[] = (float)$fila['gastos_externo'];
            $contador_anterior++;
        }
    }

    // Rellenar con ceros si faltan semanas para completar los bloques de 4
    if ($contador_actual < 4) {
        $gastos_semanales_actual  = array_pad($gastos_semanales_actual, 4, 0);
        $externo_semanales_actual = array_pad($externo_semanales_actual, 4, 0);
    }
    if ($contador_anterior < 4) {
        $gastos_semanales_anterior  = array_pad($gastos_semanales_anterior, 4, 0);
        $externo_semanales_anterior = array_pad($externo_semanales_anterior, 4, 0);
    }

    // Invertir los arreglos para orden cronológico correcto
    $gastos_actual_invertidos   = array_reverse($gastos_semanales_actual);
    $gastos_anterior_invertidos = array_reverse($gastos_semanales_anterior);
    $externo_actual_invertidos  = array_reverse($externo_semanales_actual);
    $externo_anterior_invertidos = array_reverse($externo_semanales_anterior);

    // Ajustar etiquetas semanales
    $labels_final = array_slice(array_reverse($labels_mensuales), 4, 8);

    // Si tu gráfica actual usa el bloque "actual" como base de etiquetas de 4 puntos,
    // puedes usar $externo_actual_invertidos para la línea de gastos externos.
    return [
        'gastos_semanales_actual'   => $gastos_actual_invertidos,
        'gastos_semanales_anterior' => $gastos_anterior_invertidos,
        'externo_semanales_actual'  => $externo_actual_invertidos,
        'externo_semanales_anterior' => $externo_anterior_invertidos,
        'labels_mensuales'          => $labels_final
    ];
}

// Asignación de variables lista para inyectar al JSON de JavaScript
$resultados_gastos = obtenerGastosSemanales($conexion);
if ($resultados_gastos) {
    $gastos_semanales_actual   = json_encode($resultados_gastos['gastos_semanales_actual']);
    $gastos_semanales_anterior = json_encode($resultados_gastos['gastos_semanales_anterior']);
    $gastos_semanales_externo  = json_encode($resultados_gastos['externo_semanales_actual']); // Pasamos los externos actuales
    $labels_mensuales          = json_encode($resultados_gastos['labels_mensuales']);
}

function obtenerDiferenciaPromedios($conexion)
{
    // Consulta para obtener el promedio de gastos de la semana actual y de la semana anterior
    $sql = "
    SELECT 
            SUM(CASE 
                    WHEN YEARWEEK(Fecha, 1) = YEARWEEK(CURDATE(), 1) 
                    AND fuente_dinero = 'sistema' THEN Valor 
                    ELSE 0 
                END) AS promedio_gastos_actual,
            
            SUM(CASE 
                    WHEN YEARWEEK(Fecha, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1) 
                    AND fuente_dinero = 'sistema' THEN Valor 
                    ELSE 0 
                END) AS promedio_gastos_anterior,
            SUM(CASE 
                    WHEN YEARWEEK(Fecha, 1) = YEARWEEK(CURDATE(), 1) 
                    AND fuente_dinero = 'externo' THEN Valor 
                    ELSE 0 
                END) AS total_externos_actual
        FROM gastos
        WHERE ID_Categoria_Gastos != 1
        AND (
            YEAR(Fecha) = YEAR(CURDATE()) OR 
            (MONTH(Fecha) = 12 AND YEAR(Fecha) = YEAR(CURDATE()) - 1)
        );
        ";

    $result = $conexion->query($sql);

    // Obtener los promedios
    if ($result) {
        $fila = $result->fetch_assoc();
        $promedio_gastos_actual = $fila['promedio_gastos_actual'] !== null ? $fila['promedio_gastos_actual'] : 0; // Retornar 0 si no hay gastos
        $promedio_gastos_anterior = $fila['promedio_gastos_anterior'] !== null ? $fila['promedio_gastos_anterior'] : 0; // Retornar 0 si no hay gastos
        $total_externos_actual = (float)($fila['total_externos_actual'] ?? 0);
    } else {
        echo "Error en la consulta: " . $conexion->error;
        return;
    }

    // Calcular la diferencia
    $diferencia = $promedio_gastos_actual - $promedio_gastos_anterior;

    // Formatear la diferencia para mostrarla
    if ($diferencia < 0) {
        $formato_diferencia = "↓ " . number_format(abs($diferencia), 0, '', '.') . " vs semana anterior";
        $color_diferencia = "green";
    } else {
        $formato_diferencia = "↑ " .  number_format($diferencia, 0, '', '.') . " vs semana anterior";
        $color_diferencia = "red";
    }

    // Retornar los resultados
    return [
        'promedio_actual' => $promedio_gastos_actual,
        'promedio_anterior' => $promedio_gastos_anterior,
        'total_externos' => $total_externos_actual,
        'diferencia' => $formato_diferencia,
        'color_diferencia' => $color_diferencia
    ];
}

$resultados_promedios = obtenerDiferenciaPromedios($conexion);
if ($resultados_promedios) {
    $promedio_semanal = $resultados_promedios['promedio_actual'];
    $tendencia_semanal = $resultados_promedios['diferencia'];
    $color_tendencia_semanal = $resultados_promedios['color_diferencia'];
    // Nueva variable lista para usar en tu HTML
    $total_gastos_externos_semana = $resultados_promedios['total_externos'];
}

function obtenerDiferenciaGastosMeses($conexion)
{
    // Consulta SQL corregida para capturar gastos Propios y Externos
    $consulta = "
        SELECT 
            MONTH(Fecha) AS mes, 
            YEAR(Fecha) AS anio, 
            SUM(CASE WHEN Fuente_Dinero != 'Externo' THEN Valor ELSE 0 END) AS gastos_propio,
            SUM(CASE WHEN Fuente_Dinero = 'Externo' THEN Valor ELSE 0 END) AS gastos_externo
        FROM 
            gastos
        WHERE 
            ID_Categoria_Gastos != 1
            AND Fecha >= (
                SELECT DATE_SUB(MAX(Fecha), INTERVAL 2 MONTH)
                FROM gastos
            )
        GROUP BY anio, mes 
        ORDER BY anio DESC, mes DESC
        LIMIT 2;
    ";

    $resultado = $conexion->query($consulta);

    $gastos_mes_actual = 0;
    $ext_mes_actual = 0;
    $gastos_mes_anterior = 0;

    if ($resultado) {
        $contador = 0;
        while ($fila = $resultado->fetch_assoc()) {
            if ($contador == 0) {
                $gastos_mes_actual = (float)$fila['gastos_propio'];
                $ext_mes_actual    = (float)$fila['gastos_externo'];
            } elseif ($contador == 1) {
                // Comparamos contra el neto propio del mes pasado para una métrica justa
                $gastos_mes_anterior = (float)$fila['gastos_propio'];
            }
            $contador++;
        }
    } else {
        throw new Exception("Error en la consulta: " . $conexion->error);
    }

    $diferencia = $gastos_mes_actual - $gastos_mes_anterior;

    // Normalización a clases nativas de Tailwind CSS y textos más limpios
    if ($diferencia < 0) {
        $formato_diferencia = "↓ $" . number_format(abs($diferencia), 0, '', '.');
        $color_diferencia = "green"; // Verde limpio
    } else {
        $formato_diferencia = "↑ $" . number_format(($diferencia), 0, '', '.');
        $color_diferencia = "red"; // Rojo moderno
    }

    return [
        'gastos_mes_actual'   => $gastos_mes_actual,
        'gastos_mes_externo'  => $ext_mes_actual,
        'gastos_mes_anterior' => $gastos_mes_anterior,
        'diferencia'          => $formato_diferencia,
        'color_diferencia'    => $color_diferencia
    ];
}

function obtenerIngresosMesActual($conexion)
{
    // Se eliminó la restricción externa por si tus ingresos base provienen de transferencias declaradas externas
    $consulta = "SELECT SUM(Valor) AS total_ingresos FROM gastos WHERE MONTH(Fecha) = MONTH(CURDATE()) AND YEAR(Fecha) = YEAR(CURDATE()) AND ID_Categoria_Gastos = 1";

    $resultado = $conexion->query($consulta);
    $total_ingresos = 0;

    if ($resultado && $fila = $resultado->fetch_assoc()) {
        $total_ingresos = (float)$fila['total_ingresos'];
    }
    return $total_ingresos;
}

// Ejecución y Cálculos
$resultados_meses        = obtenerDiferenciaGastosMeses($conexion);
$gasto_mensual_total     = $resultados_meses['gastos_mes_actual'];
$gasto_mensual_externo   = $resultados_meses['gastos_mes_externo'];
$tendencia_mensual       = $resultados_meses['diferencia'];
$color_tendencia_mensual = $resultados_meses['color_diferencia'];

$total_ingresos = obtenerIngresosMesActual($conexion);

$porcentaje_presupuesto = ($total_ingresos > 0) ? round(($gasto_mensual_total / $total_ingresos) * 100, 1) : 0;
$presupuesto_restante   = $total_ingresos - $gasto_mensual_total;
function obtenerCategoriasGastos($conexion)
{

    $ORDEN = "ORDER BY `categorias` ASC";

    $ORDEN_OLD = "ORDER BY 
        semanal DESC, semana_anterior DESC;";
    // Consulta SQL para obtener los datos de gastos por categoría
    $sql = "
    SELECT 
        c.Nombre AS categorias, 
        SUM(CASE 
                WHEN YEARWEEK(g.Fecha, 1) = YEARWEEK(CURDATE(), 1) 
                THEN g.Valor 
                ELSE 0 
            END) AS semanal, 
        
        SUM(CASE 
                WHEN YEARWEEK(g.Fecha, 1) = YEARWEEK(CURDATE() - INTERVAL 1 WEEK, 1) 
                THEN g.Valor 
                ELSE 0 
            END) AS semana_anterior 
    FROM 
        gastos g 
    JOIN 
        categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
    WHERE 
        g.ID_Categoria_Gastos != 1 
        AND g.ID_Categoria_Gastos != 2
        AND (
            YEAR(g.Fecha) = YEAR(CURDATE()) OR 
            (MONTH(g.Fecha) = 12 AND YEAR(g.Fecha) = YEAR(CURDATE()) - 1)
        )
        AND Fuente_Dinero != 'Externo'
    GROUP BY 
        c.Nombre
        $ORDEN
        ";

    $result = $conexion->query($sql);

    // Arreglo para almacenar los datos formateados
    $categorias_gastos = [];

    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $semanal = $fila['semanal'];
            $semana_anterior = $fila['semana_anterior'];


            // Calcular la tendencia de esta semana en comparación con la semana pasada
            $trend = ($semana_anterior != 0) ? (($semanal - $semana_anterior) / $semana_anterior) * 100 : null;

            // Formatear la tendencia para mostrar
            $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "0%";

            // Alternativamente, puedes definir $trend_format como "N/A" si no hay datos
            // $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "N/A";

            // Agregar datos al arreglo
            $categorias_gastos[] = [
                "category" => $fila['categorias'],
                "weekly" => $semanal,
                "monthly" => $semana_anterior,
                "trend" => $trend_format
            ];
        }
    } else {
        echo "Error en la consulta: " . $conexion->error;
    }

    return $categorias_gastos;
}

$categorias_gastos = obtenerCategoriasGastos($conexion);

function obtenerCategoriasGastosMes($conexion)
{

    $ORDEN = "ORDER BY `categorias` ASC";

    $ORDEN_OLD = "       ORDER BY 
            mes_actual DESC, mes_anterior DESC;";
    // Consulta SQL para obtener los datos de gastos por categoría
    $sql = "
        SELECT 
            c.Nombre AS categorias, 
            SUM(CASE 
                    WHEN MONTH(g.Fecha) = MONTH(CURDATE()) AND YEAR(g.Fecha) = YEAR(CURDATE()) 
                    THEN g.Valor ELSE 0 
                END) AS mes_actual, 
            SUM(CASE 
                    WHEN MONTH(g.Fecha) = MONTH(CURDATE() - INTERVAL 1 MONTH) AND YEAR(g.Fecha) = YEAR(CURDATE() - INTERVAL 1 MONTH) 
                    THEN g.Valor ELSE 0 
                END) AS mes_anterior 
        FROM 
            gastos g 
        JOIN 
            categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
        WHERE 
            g.ID_Categoria_Gastos NOT IN (1, 2)
            AND g.Fuente_Dinero != 'Externo'
        GROUP BY 
            c.Nombre
        $ORDEN
    ";

    $result = $conexion->query($sql);

    // Arreglo para almacenar los datos formateados
    $categorias_gastos = [];

    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $mes_actual = $fila['mes_actual'];
            $mes_anterior = $fila['mes_anterior'];


            // Calcular la tendencia de esta semana en comparación con la semana pasada
            $trend = ($mes_anterior != 0) ? (($mes_actual - $mes_anterior) / $mes_anterior) * 100 : null;

            // Formatear la tendencia para mostrar
            $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "0%";

            // Alternativamente, puedes definir $trend_format como "N/A" si no hay datos
            // $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "N/A";

            // Agregar datos al arreglo
            $categorias_gastos[] = [
                "category" => $fila['categorias'],
                "weekly" => $mes_actual,
                "monthly" => $mes_anterior,
                "trend" => $trend_format
            ];
        }
    } else {
        echo "Error en la consulta: " . $conexion->error;
    }

    return $categorias_gastos;
}

$categorias_gastos_mensual = obtenerCategoriasGastosMes($conexion);


function obtenerCategoriasGastosAnuales($conexion)
{
    $ORDEN = "ORDER BY `categorias` ASC";

    $ORDEN_OLD = "               ORDER BY 
            ano_actual DESC, ano_pasado DESC";

    // Consulta SQL para obtener los datos de gastos por categoría
    $sql = "
        SELECT 
            c.Nombre AS categorias, 
            SUM(CASE 
                    WHEN YEAR(g.Fecha) = YEAR(CURDATE()) 
                    THEN g.Valor ELSE 0 
                END) AS ano_actual, 
            SUM(CASE 
                    WHEN YEAR(g.Fecha) = YEAR(CURDATE()) - 1 
                    THEN g.Valor ELSE 0 
                END) AS ano_pasado 
        FROM 
            gastos g 
        JOIN 
            categorias_gastos c ON g.ID_Categoria_Gastos = c.ID 
        WHERE 
            g.ID_Categoria_Gastos NOT IN (1)
            AND g.Fuente_Dinero != 'Externo'
        GROUP BY 
            c.Nombre
$ORDEN
    ";

    $result = $conexion->query($sql);

    // Arreglo para almacenar los datos formateados
    $categorias_gastos = [];

    if ($result) {
        while ($fila = $result->fetch_assoc()) {
            $año_actual = $fila['ano_actual'];
            $año_pasado = $fila['ano_pasado'];

            // Calcular la tendencia comparando los gastos entre el año actual y el año pasado
            $trend = ($año_pasado != 0) ? (($año_actual - $año_pasado) / $año_pasado) * 100 : null;

            // Formatear la tendencia para mostrar
            $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "0%";

            // Alternativamente, puedes definir $trend_format como "N/A" si no hay datos
            // $trend_format = $trend !== null ? ($trend > 0 ? "+" : "") . round($trend, 2) . "%" : "N/A";

            // Agregar datos al arreglo
            $categorias_gastos[] = [
                "category" => $fila['categorias'],
                "year_current" => $año_actual,
                "year_previous" => $año_pasado,
                "trend" => $trend_format
            ];
        }
    } else {
        echo "Error en la consulta: " . $conexion->error;
    }

    return $categorias_gastos;
}

$categorias_gastos_anual = obtenerCategoriasGastosAnuales($conexion);

// Obtener totales por medio de pago para el mes actual
// Obtener totales (propios y externos) por medio de pago para el mes actual
$sql_medios = "SELECT 
        id_medio_pago, 
        SUM(CASE WHEN Fuente_Dinero != 'Externo' THEN Valor ELSE 0 END) as total_propio,
        SUM(CASE WHEN Fuente_Dinero = 'Externo' THEN Valor ELSE 0 END) as total_externo
    FROM gastos 
    WHERE MONTH(Fecha) = MONTH(CURDATE()) AND YEAR(Fecha) = YEAR(CURDATE()) 
      AND ID_Categoria_Gastos NOT IN (1, 2)
    GROUP BY id_medio_pago;";

$stmt_medios = $pdo->query($sql_medios);
$datos_medios = $stmt_medios->fetchAll(PDO::FETCH_ASSOC);

// Mapeo consistente de nombres y colores base (propios)
$nombres_medios = [1 => 'Débito', 2 => 'Crédito', 3 => 'Efectivo'];
$colores_medios = [1 => 'rgba(13, 71, 161, 0.75)', 2 => 'rgba(230, 81, 0, 0.75)', 3 => 'rgba(27, 94, 32, 0.75)'];

$pie_medios_data = [];

foreach ($datos_medios as $row) {
    $id = $row['id_medio_pago'];
    $nombre = $nombres_medios[$id] ?? 'Otro';

    $pie_medios_data[] = [
        'name' => $nombre,
        'propio' => (int)$row['total_propio'],
        'externo' => (int)$row['total_externo'],
        'color' => $colores_medios[$id] ?? 'rgba(108, 117, 125, 0.75)'
    ];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Gastos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <style>
        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #f6f8fc 0%, #edf1f7 100%);
        }

        .chart-container {
            height: 300px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .red {
            color: rgba(239, 68, 68);
        }

        @media only screen and (max-width: 600px) {
            .chart-container {
                position: relative;
                height: 400px;
                margin-bottom: 10px;
                width: 200px;
            }

            .card {
                width: 100%;
                padding-left: 15px;
            }

            .grid {
                display: flex;
                flex-wrap: wrap;
                /* Permite que los elementos pasen a la siguiente fila si no caben */
                gap: 1rem;
                /* Espacio entre los elementos */
            }

        }

        /* Estilos para la barra de desplazamiento (scrollbar) */
        .scrollbar-thin {
            scrollbar-width: thin;
            /* Estilo del navegador Firefox */
        }

        .scrollbar-thumb-gray-400 {
            scrollbar-color: #9CA3AF #E5E7EB;
            /* Color del "pulgar" y la pista en Firefox */
        }

        /* Para navegadores basados en WebKit (Chrome, Safari) */
        .scrollbar-thin::-webkit-scrollbar {
            height: 8px;
            /* Altura del scrollbar */
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background-color: #E5E7EB;
            /* Color de la pista */
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: #9CA3AF;
            /* Color del "pulgar" */
            border-radius: 10px;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ddd;
        }

        .header-left {
            text-align: start;
        }

        .header-right {
            text-align: end;
        }

        .btn-custom {
            display: inline-flex;
            align-items: center;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 1rem;
            color: #6c757d;
            /* Color del texto inicial */
            border: 2px solid #6c757d;
            /* Borde inicial */
            border-radius: 8px;
            text-decoration: none;
            background-color: transparent;
            transition: all 0.3s ease-in-out;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-custom i {
            font-size: 1.25rem;
            margin-right: 8px;
        }

        .btn-custom:hover {
            background-color: rgb(164, 164, 164);
            /* Color de fondo al pasar el mouse */
            color: white;
            /* Color del texto en hover */
            border-color: #6c757d;
            transform: translateY(-2px);
            /* Efecto de elevación */
            box-shadow: 4px 4px 10px rgba(230, 236, 242, 0.2);
        }
    </style>
</head>

<body class="gradient-bg min-h-screen p-6">

    <div class="max-w-7xl mx-auto">

        <header class="row align-items-center mt-3">
            <div class="col-6 text-start">
                <h1 class="text-4xl font-bold text-gray-800">Control de Gastos</h1>
                <p class="text-gray-600">Resumen Diario y Semanal</p>
            </div>
            <div class="col-6 text-end">
                <a href="./resumen_finanzas.php" class="btn-custom">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-bar-graph" viewBox="0 0 16 16" style="margin-right:10px">
                        <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5" />
                    </svg>
                    Dashboard Gastos
                </a>
            </div>
        </header>
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="card p-6">
                <h2 class="text-xl font-semibold mb-4">Resumen Diario</h2>
                <canvas id="weeklyChart" class="chart-container"></canvas>
            </div>
            <div class="card p-6">
                <h2 class="text-xl font-semibold mb-4">Resumen Semanal</h2>
                <canvas id="monthlyChart" class="chart-container"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Gasto Semanal Promedio</h3>
                    <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($promedio_semanal, 0, '', '.'); ?></p>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-50 flex items-center justify-between">
                    <span class="text-xs font-medium text-<?php echo $color_tendencia_semanal; ?>-600 bg-<?php echo $color_tendencia_semanal; ?>-50/50 px-2 py-0.5 rounded">
                        <?php echo $tendencia_semanal; ?>
                    </span>
                    <?php if ($total_gastos_externos_semana > 0): ?>
                        <span class="text-[11px] text-gray-400 font-medium">
                            Ext: +$<?php echo number_format($total_gastos_externos_semana, 0, '', '.'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Gasto Mensual Neto</h3>
                    <p class="text-2xl font-bold text-gray-800">$<?php echo number_format($gasto_mensual_total, 0, '', '.'); ?></p>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-50 flex items-center justify-between">
                    <span class="text-xs font-medium text-<?php echo $color_tendencia_semanal; ?>-600 bg-<?php echo $color_tendencia_semanal; ?>-50/50 px-2 py-0.5 rounded">
                        <?php echo $tendencia_mensual; ?> vs mes anterior
                    </span>
                    <?php if ($gasto_mensual_externo > 0): ?>
                        <span class="text-[11px] text-gray-400 font-medium">
                            Ext: +$<?php echo number_format($gasto_mensual_externo, 0, '', '.'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div>
                    <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Presupuesto Restante</h3>
                    <p class="text-2xl font-bold <?php echo $presupuesto_restante < 0 ? 'text-red-600' : 'text-gray-800'; ?>">
                        $<?php echo number_format($presupuesto_restante, 0, '', '.'); ?>
                    </p>
                </div>
                <div class="mt-3 pt-2 border-t border-gray-50 flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 bg-gray-50 px-2 py-0.5 rounded">
                        <?php echo $porcentaje_presupuesto . '% consumido'; ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 mb-8">
            <div class="mb-4">
                <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Distribución por Medios de Pago</h3>
            </div>
            <div class="relative" style="height: 240px;"> <canvas id="chartMediosPago"></canvas>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4 mb-8">

            <div class="card p-4 sm:p-6">
                <h2 class="text-lg sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">Desglose de Gastos Semanal</h2>
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200">
                    <div class="overflow-y-auto max-h-96"> <!-- Contenedor con scroll vertical -->
                        <table class="divide-y divide-gray-200 table">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Categoría</th>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Esta Semana</th>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider hidden md:table-cell">Semana Anterior</th>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Tendencia</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $total_semanal = 0;
                                $total_semanal_anterior = 0;
                                foreach ($categorias_gastos as $categoria): ?>
                                    <?php
                                    $tendencia_clase = (strpos($categoria['trend'], '+') === 0) ? 'text-red-600 font-medium' : 'text-green-600 font-medium';
                                    $total_semanal += $categoria['weekly']; // Sumar valores semanales
                                    $total_semanal_anterior += $categoria['monthly']; // Sumar valores mensuales
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-xs sm:text-sm font-medium text-gray-900"><?= htmlspecialchars($categoria['category']) ?></div>
                                            </div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                            <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['weekly'], 0, '', '.') ?></div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                            <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['monthly'], 0, '', '.') ?></div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                            <span class="<?= $tendencia_clase ?> text-xs sm:text-sm"><?= htmlspecialchars($categoria['trend']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach;
                                $tendencia_semanal = ($total_semanal_anterior != 0) ? (($total_semanal - $total_semanal_anterior) / $total_semanal_anterior) * 100 : null;
                                $tendencia_semanal_formateada = $tendencia_semanal !== null ? ($tendencia_semanal > 0 ? "+" : "") . round($tendencia_semanal, 2) . "%" : "0%";
                                ?>
                                <!-- Fila para totales -->
                                <tr class="font-semibold bg-gray-100">
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm">Total</div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_semanal, 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_semanal_anterior, 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="<?= $tendencia_semanal < 0 ? 'text-green-600' : 'text-red-600' ?> text-xs sm:text-sm"><?= $tendencia_semanal_formateada ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="card p-6">
                <h2 class="text-lg sm:text-2xl font-bold text-gray-800 mb-6 sm:mb-11">Desglose de Gastos Mensual</h2>
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 ">
                    <div class="overflow-y-auto max-h-96"> <!-- Contenedor con scroll vertical -->
                        <table class="divide-y divide-gray-200 table">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Categoría</th>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Este Mes</th>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider hidden md:table-cell">Mes Anterior</th>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Tendencia</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $total_mensual = 0;
                                $total_mensual_anterior = 0;
                                foreach ($categorias_gastos_mensual as $categoria): ?>
                                    <?php $tendencia_clase = (strpos($categoria['trend'], '+') === 0) ? 'text-red-600 font-medium' : 'text-green-600 font-medium';
                                    $total_mensual += $categoria['weekly']; // Sumar valores semanales
                                    $total_mensual_anterior += $categoria['monthly']; // Sumar valores mensuales 
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-xs sm:text-sm font-medium text-gray-900"><?= htmlspecialchars($categoria['category']) ?></div>
                                            </div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                            <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['weekly'], 0, '', '.') ?></div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                            <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['monthly'], 0, '', '.') ?></div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                            <span class="<?= $tendencia_clase ?> text-xs sm:text-sm"><?= htmlspecialchars($categoria['trend']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach;
                                // Calcular tendencia para la semana y el mes
                                $tendencia_mensual = ($total_mensual_anterior != 0) ? (($total_mensual - $total_mensual_anterior) / $total_mensual_anterior) * 100 : null;

                                // Formatear las tendencias para mostrar
                                $tendencia_mensual_formateada = $tendencia_mensual !== null ? ($tendencia_mensual > 0 ? "+" : "") . round($tendencia_mensual, 2) . "%" : "0%";
                                ?>

                                <!-- Fila para totales -->
                                <tr class="font-semibold bg-gray-100">
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm">Total</div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_mensual, 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_mensual_anterior, 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="<?= $tendencia_mensual < 0 ? 'text-green-600' : 'text-red-600' ?> text-xs sm:text-sm"><?= $tendencia_mensual_formateada ?></span> <!-- Mostrar la tendencia semanal -->
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-1 gap-8 mb-12">
            <div class="card p-8 shadow-lg rounded-lg bg-white">
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6 sm:mb-10">Desglose de Gastos Anual</h2>
                <div class="overflow-x-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 rounded-lg shadow-md">
                    <div class="overflow-y-auto max-h-96"> <!-- Contenedor con scroll vertical -->
                        <table class="divide-y divide-gray-200 table-auto w-full min-w-max">
                            <thead class="bg-gray-100 text-sm">
                                <tr>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Categoría</th>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Este Año</th>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider hidden md:table-cell">Año Anterior</th>
                                    <th class="px-3 sm:px-6 py-2 sm:py-4 text-left text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider">Tendencia</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php
                                $total_anual = 0;
                                $total_anual_anterior = 0;
                                foreach ($categorias_gastos_anual as $categoria): ?>
                                    <?php
                                    $tendencia_clase = (strpos($categoria['trend'], '+') === 0) ? 'text-red-600 font-medium' : 'text-green-600 font-medium';
                                    $total_anual += $categoria['year_current']; // Sumar valores semanales
                                    $total_anual_anterior += $categoria['year_previous']; // Sumar valores mensuales 
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="text-xs sm:text-sm font-medium text-gray-900"><?= htmlspecialchars($categoria['category']) ?></div>
                                            </div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                            <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['year_current'], 0, '', '.') ?></div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                            <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($categoria['year_previous'], 0, '', '.') ?></div>
                                        </td>
                                        <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                            <span class="<?= $tendencia_clase ?> text-xs sm:text-sm"><?= htmlspecialchars($categoria['trend']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach;
                                // Calcular tendencia para la semana y el mes
                                $tendencia_anual = ($total_anual_anterior != 0) ? (($total_anual - $total_anual_anterior) / $total_anual_anterior) * 100 : null;

                                // Formatear las tendencias para mostrar
                                $tendencia_anual_formateada = $tendencia_anual !== null ? ($tendencia_anual > 0 ? "+" : "") . round($tendencia_anual, 2) . "%" : "0%";
                                ?>

                                <!-- Fila para totales -->
                                <tr class="font-semibold bg-gray-100">
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm">Total</div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_anual, 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap hidden md:table-cell">
                                        <div class="text-xs sm:text-sm text-gray-900">$<?= number_format($total_anual_anterior, 0, '', '.') ?></div>
                                    </td>
                                    <td class="px-3 sm:px-6 py-2 sm:py-4 whitespace-nowrap">
                                        <span class="<?= $tendencia_anual < 0 ? 'text-green-600' : 'text-red-600' ?> text-xs sm:text-sm"><?= $tendencia_anual_formateada ?></span> <!-- Mostrar la tendencia semanal -->
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <script>
        // Datos de ejemplo
        // Estructura de datos actualizando los gastos semanales desglosados
        const weeklyData = {
            labels: <?php echo $labels_semanales; ?>, // Días de la semana
            propio: <?php echo $gastos_propio; ?>, // Gastos propios por día
            externo: <?php echo $gastos_externo; ?> // Gastos externos por día
        };

        const monthlyData = {
            labels: <?php echo $labels_mensuales ?>,
            current: <?php echo $gastos_semanales_actual ?>, // Tu gasto real neto actual
            previous: <?php echo $gastos_semanales_anterior ?>, // Tu gasto real neto del mes pasado
            external: <?php echo $gastos_semanales_externo ?> // NUEVO: Solo los flujos externos de este mes
        };

        const expensesCategories = <?php echo json_encode($categorias_gastos); ?>;

        // Configurar gráfico semanal con barras apiladas
        new Chart(document.getElementById('weeklyChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: weeklyData.labels,
                datasets: [{
                        label: 'Gastos Propios',
                        data: weeklyData.propio,
                        backgroundColor: 'rgba(59, 130, 246, 0.6)', // Azul original sutilmente más denso
                        borderRadius: 6
                    },
                    {
                        label: 'Gastos Externos',
                        data: weeklyData.externo,
                        backgroundColor: 'rgba(173, 181, 189, 0.7)', // Gris (#adb5bd) para la parte externa
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true, // Activamos la leyenda para que se entienda la diferencia del gris
                        position: 'top',
                        labels: {
                            boxWidth: 12
                        }
                    },
                    title: {
                        display: true,
                        text: 'Gastos Diarios de la Semana'
                    },
                    tooltip: {
                        // Modificamos el tooltip para que muestre el total combinado del día al pasar el cursor
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.raw || 0;
                                return label + ': $' + new Intl.NumberFormat('es-CL').format(value);
                            },
                            footer: function(tooltipItems) {
                                let total = 0;
                                tooltipItems.forEach(function(item) {
                                    total += item.raw;
                                });
                                return 'Total Día: $' + new Intl.NumberFormat('es-CL').format(total);
                            }
                        }
                    }
                },
                scales: {
                    // CRÍTICAL: Estas propiedades fuerzan a Chart.js a montar las barras una sobre otra
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            // Formatea los números del eje lateral con puntos
                            callback: function(value) {
                                return '$' + new Intl.NumberFormat('es-CL').format(value);
                            }
                        }
                    }
                }
            }
        });

        // Configurar gráfico mensual con comparación
        // Configurar gráfico mensual con comparación y desglose externo
        new Chart(document.getElementById('monthlyChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                        label: 'Mes Anterior (Neto)',
                        data: monthlyData.previous,
                        borderColor: 'rgb(156, 163, 175)',
                        backgroundColor: 'rgba(156, 163, 175, 0.1)',
                        //borderDash: [5, 5], // Línea discontinua para indicar que es pasado
                        fill: true,
                        tension: 0.2
                    },
                    {
                        label: 'Mes Actual (Neto)',
                        data: monthlyData.current,
                        borderColor: 'rgb(59, 130, 246)', // Azul principal
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.2,
                        borderWidth: 3 // Más gruesa para destacar que es el flujo actual
                    },
                    {
                        label: 'Gastos Externos (Mes Act.)',
                        data: monthlyData.external,
                        borderColor: 'rgb(206, 212, 218)', // Gris claro/plateado para el flujo externo
                        backgroundColor: 'transparent',
                        borderDash: [3, 3],
                        fill: false,
                        tension: 0.2,
                        pointRadius: 4,
                        pointBackgroundColor: 'rgb(173, 181, 189)'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Comparativa de Gastos Mensuales y Flujo Externo'
                    },
                    tooltip: {
                        // Formateador chileno para los números dentro del tooltip interactivo
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.raw || 0;
                                return label + ': $' + new Intl.NumberFormat('es-CL').format(value);
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + new Intl.NumberFormat('es-CL').format(value);
                            }
                        }
                    }
                }
            }
        });
        (function() {
            const ctx = document.getElementById('chartMediosPago').getContext('2d');

            // Datos estructurados desde PHP
            const dataMedios = <?php echo json_encode($pie_medios_data); ?>;

            const myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: dataMedios.map(item => item.name),
                    datasets: [{
                            label: 'Gasto Propio',
                            data: dataMedios.map(item => item.propio),
                            backgroundColor: dataMedios.map(item => item.color),
                            borderRadius: 6,
                            barThickness: 20
                        },
                        {
                            label: 'Gasto Externo',
                            data: dataMedios.map(item => item.externo),
                            backgroundColor: 'rgba(173, 181, 189, 0.7)', // Gris neutro y plano para lo ajeno
                            borderRadius: 6,
                            barThickness: 20
                        }
                    ]
                },
                options: {
                    indexAxis: 'y', // Fuerza el diseño horizontal
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true, // Activamos una leyenda sutil superior
                            position: 'top',
                            labels: {
                                boxWidth: 10,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index', // Muestra propio, externo y total al mismo tiempo en el tooltip
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    let value = context.raw || 0;
                                    return label + ': ' + new Intl.NumberFormat('es-CL', {
                                        style: 'currency',
                                        currency: 'CLP',
                                        maximumFractionDigits: 0
                                    }).format(value);
                                },
                                footer: function(tooltipItems) {
                                    let total = 0;
                                    tooltipItems.forEach(item => total += item.raw);
                                    return 'Total: ' + new Intl.NumberFormat('es-CL', {
                                        style: 'currency',
                                        currency: 'CLP',
                                        maximumFractionDigits: 0
                                    }).format(total);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true, // Apila los conjuntos en el eje X
                            ticks: {
                                font: {
                                    size: 11
                                },
                                callback: value => new Intl.NumberFormat('es-CL', {
                                    notation: 'compact'
                                }).format(value)
                            },
                            grid: {
                                display: false
                            } // Remueve líneas verticales molestas para un diseño más limpio
                        },
                        y: {
                            stacked: true, // Apila los conjuntos en el eje Y
                            ticks: {
                                font: {
                                    size: 12,
                                    weight: '500'
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Manejo de redimensionamiento por pestañas
            const tabEl = document.querySelector('#medios-tab');
            if (tabEl) {
                tabEl.addEventListener('shown.bs.tab', () => {
                    myChart.resize();
                });
            }
        })();
    </script>
</body>

</html>