<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$conn = new mysqli("localhost", "root", "", "Escola");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["erro" => "Conexão falhou: " . $conn->connect_error]);
    exit;
}

$acao  = $_GET['acao']  ?? '';
$turma = $_GET['turma'] ?? '2D';
$turma = $conn->real_escape_string($turma);

// =============================================
// AÇÃO: dias — quais dias têm chamada registrada
// =============================================
if ($acao === 'dias') {
    $sql = "
        SELECT DISTINCT f.data_chamada
        FROM frequencia f
        INNER JOIN alunos a ON a.id = f.aluno_id
        INNER JOIN turmas t ON t.id = a.turma_id
        WHERE t.nome = '$turma'
        ORDER BY f.data_chamada DESC
        LIMIT 90
    ";
    $r    = $conn->query($sql);
    $dias = [];
    while ($row = $r->fetch_assoc()) $dias[] = $row['data_chamada'];
    echo json_encode($dias);
    exit;
}

// =============================================
// AÇÃO: dia — chamada completa de uma data
// =============================================
if ($acao === 'dia') {
    $data = $_GET['data'] ?? date('Y-m-d');
    $data = $conn->real_escape_string($data);

    $sql = "
        SELECT a.id, a.nome, f.aula, f.status
        FROM alunos a
        INNER JOIN turmas t ON t.id = a.turma_id
        LEFT JOIN frequencia f
          ON f.aluno_id = a.id AND f.data_chamada = '$data'
        WHERE t.nome = '$turma' AND a.ativo = 1
        ORDER BY a.nome, f.aula
    ";
    $r      = $conn->query($sql);
    $alunos = [];
    while ($row = $r->fetch_assoc()) {
        $id = $row['id'];
        if (!isset($alunos[$id])) {
            $alunos[$id] = ['id' => $id, 'nome' => $row['nome'], 'aulas' => array_fill(0, 9, null)];
        }
        if ($row['aula']) {
            $alunos[$id]['aulas'][$row['aula'] - 1] = $row['status'];
        }
    }
    echo json_encode(array_values($alunos));
    exit;
}

// =============================================
// AÇÃO: risco — alunos com >25% de faltas no período
// =============================================
if ($acao === 'risco') {
    $inicio = $_GET['inicio'] ?? date('Y-m-01');
    $fim    = $_GET['fim']    ?? date('Y-m-d');
    $inicio = $conn->real_escape_string($inicio);
    $fim    = $conn->real_escape_string($fim);

    $sql = "
        SELECT
            a.nome,
            COUNT(*) AS total,
            SUM(f.status = 'F') AS faltas,
            SUM(f.status = 'P') AS presencas,
            SUM(f.status = 'J') AS justificadas,
            ROUND(SUM(f.status = 'F') / COUNT(*) * 100, 1) AS pct_falta
        FROM frequencia f
        INNER JOIN alunos a ON a.id = f.aluno_id
        INNER JOIN turmas t ON t.id = a.turma_id
        WHERE t.nome = '$turma'
          AND f.data_chamada BETWEEN '$inicio' AND '$fim'
          AND a.ativo = 1
        GROUP BY a.id, a.nome
        HAVING pct_falta > 25
        ORDER BY pct_falta DESC
    ";
    $r    = $conn->query($sql);
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    echo json_encode($list);
    exit;
}

// =============================================
// AÇÃO: comparar — presença de um aluno em 2 meses
// =============================================
if ($acao === 'comparar') {
    $aluno_id = intval($_GET['aluno_id'] ?? 0);
    $mes1     = $conn->real_escape_string($_GET['mes1'] ?? '');
    $mes2     = $conn->real_escape_string($_GET['mes2'] ?? '');

    function resumoMes($conn, $aluno_id, $mes) {
        $inicio = $mes . '-01';
        $fim    = date('Y-m-t', strtotime($inicio));
        $sql = "
            SELECT
                f.data_chamada,
                SUM(f.status='P') AS p,
                SUM(f.status='F') AS f_count,
                SUM(f.status='J') AS j
            FROM frequencia f
            WHERE f.aluno_id = $aluno_id
              AND f.data_chamada BETWEEN '$inicio' AND '$fim'
            GROUP BY f.data_chamada
            ORDER BY f.data_chamada
        ";
        $r    = $conn->query($sql);
        $dias = [];
        while ($row = $r->fetch_assoc()) $dias[] = $row;
        return $dias;
    }

    echo json_encode([
        'mes1' => resumoMes($conn, $aluno_id, $mes1),
        'mes2' => resumoMes($conn, $aluno_id, $mes2),
    ]);
    exit;
}

// =============================================
// AÇÃO: alunos — lista de alunos da turma
// =============================================
if ($acao === 'alunos') {
    $sql = "
        SELECT a.id, a.nome
        FROM alunos a
        INNER JOIN turmas t ON t.id = a.turma_id
        WHERE t.nome = '$turma' AND a.ativo = 1
        ORDER BY a.nome
    ";
    $r    = $conn->query($sql);
    $list = [];
    while ($row = $r->fetch_assoc()) $list[] = $row;
    echo json_encode($list);
    exit;
}

echo json_encode(["erro" => "Ação inválida"]);
