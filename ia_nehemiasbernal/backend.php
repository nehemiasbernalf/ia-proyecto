<?php
// Silenciar cualquier advertencia o aviso de PHP que pueda corromper la salida JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'errors' => ['Método no permitido.']]);
    exit;
}

// Captura y saneamiento de datos enviados por el formulario
$padre_val = isset($_POST['padre']) ? trim($_POST['padre']) : '';
$madre_val = isset($_POST['madre']) ? trim($_POST['madre']) : '';
$cut_val   = isset($_POST['cut'])   ? trim($_POST['cut'])   : '';

$errors = [];

// Validación de existencia de datos
if ($padre_val === '' || $madre_val === '' || $cut_val === '') {
    $errors[] = "Todos los campos (Padre, Madre e Índice de Corte) son obligatorios.";
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Validación de formato binario
if (!preg_match('/^[01]+$/', $padre_val) || !preg_match('/^[01]+$/', $madre_val)) {
    $errors[] = "Los cromosomas deben contener exclusivamente caracteres binarios (0 y 1).";
}

$lenPadre = strlen($padre_val);
$lenMadre = strlen($madre_val);

// Validación de longitudes idénticas
if ($lenPadre !== $lenMadre) {
    $errors[] = "Los cromosomas deben tener exactamente la misma longitud.";
}

// Validación de tamaño mínimo
if ($lenPadre < 2) {
    $errors[] = "La longitud mínima de los cromosomas debe ser de 2 bits.";
}

// Validación del punto de corte numérico y rangos válidos
if (!ctype_digit($cut_val)) {
    $errors[] = "La posición de corte debe ser un número entero.";
} else {
    $cut = intval($cut_val);
    if ($lenPadre >= 2) {
        $maxCorte = $lenPadre - 1;
        if ($cut < 1 || $cut > $maxCorte) {
            $errors[] = "La posición de corte debe estar entre 1 y $maxCorte.";
        }
    }
}

// Si se encontraron errores de validación, retornar de inmediato
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Operación física de Crossover Monocorte pura
$child1 = substr($padre_val, 0, $cut) . substr($madre_val, $cut);
$child2 = substr($madre_val, 0, $cut) . substr($padre_val, $cut);

/**
 * Procesa el modelado omitiendo los textos descriptivos posicionales de los bits.
 */
function calcularFitnessEvolutivo($binario) {
    $longitud = strlen($binario);
    $decimal = 0;
    
    $html_pasos = "";
    $terminos_suma = [];
    
    for ($i = 0; $i < $longitud; $i++) {
        $bit = intval($binario[$i]);
        $potencia = $longitud - 1 - $i;
        
        // Optimización por desplazamiento de bits para prevenir problemas de rendimiento
        $valorPosicional = 1 << $potencia; 
        
        $resultadoTermino = $bit * $valorPosicional;
        $decimal += $resultadoTermino;
        
        $terminos_suma[] = $resultadoTermino;
        $html_pasos .= "({$bit} × 2^{$potencia}) = {$resultadoTermino}<br>";
    }
    
    // Formato matemático unificado usando "X:"
    $html_pasos .= "X: " . implode(" + ", $terminos_suma) . " = {$decimal}<br>";
    
    $fitness_cuadrado = pow($decimal, 2);
    $html_pasos .= "<div class='math-total'>Fitness Final: {$decimal}² = <strong>{$fitness_cuadrado}</strong></div>";
    
    return [
        'decimal' => $decimal,
        'fitness_cuadrado' => $fitness_cuadrado,
        'proceso' => $html_pasos
    ];
}

$fit1 = calcularFitnessEvolutivo($child1);
$fit2 = calcularFitnessEvolutivo($child2);

// Retorno unificado de la estructura JSON
echo json_encode([
    'success' => true,
    'data' => [
        'corte'  => $cut,
        'padre'  => $padre_val,
        'madre'  => $madre_val,
        'hijo1'  => [
            'binario' => $child1,
            'fitness_cuadrado' => $fit1['fitness_cuadrado'],
            'proceso' => $fit1['proceso']
        ],
        'hijo2'  => [
            'binario' => $child2,
            'fitness_cuadrado' => $fit2['fitness_cuadrado'],
            'proceso' => $fit2['proceso']
        ]
    ]
]);