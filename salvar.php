<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "Escola");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["erro" => "Conexão falhou: " . $conn->connect_error]);
    exit;
}

$dados = json_decode(file_get_contents("php://input"), true);

if (!$dados) {
    echo json_encode(["erro" => "Nenhum dado recebido"]);
    exit;
}

$data = date("Y-m-d");
$salvos = 0;
$erros  = [];

foreach ($dados as $item) {

    $nome  = $conn->real_escape_string(trim($item['nome']));
    $turma = $conn->real_escape_string(trim($item['turma'] ?? '2D'));
    $aulas = $item['aulas'] ?? [];

    // ── 1. Garante que a turma existe ──────────────────────────────
    $conn->query("INSERT IGNORE INTO turmas (nome) VALUES ('$turma')");

    // ── 2. Busca o aluno pelo nome + turma ─────────────────────────
    $sql = "
        SELECT a.id FROM alunos a
        INNER JOIN turmas t ON t.id = a.turma_id
        WHERE a.nome = '$nome' AND t.nome = '$turma' AND a.ativo = 1
        LIMIT 1
    ";
    $res = $conn->query($sql);

    if ($res && $res->num_rows > 0) {
        // Aluno já existe — usa o id encontrado
        $aluno_id = $res->fetch_assoc()['id'];
    } else {
        // Aluno não existe — insere automaticamente
        $ins = $conn->query("
            INSERT INTO alunos (nome, turma_id)
            SELECT '$nome', id FROM turmas WHERE nome = '$turma' LIMIT 1
        ");
        if (!$ins) {
            $erros[] = "Erro ao inserir aluno '$nome': " . $conn->error;
            continue;
        }
        $aluno_id = $conn->insert_id;
    }

    // ── 3. Salva cada aula com INSERT ... ON DUPLICATE KEY UPDATE ──
    foreach ($aulas as $idx => $status) {
        $aula   = intval($idx) + 1;
        $status = $conn->real_escape_string(strtoupper(trim($status)));

        // Aceita apenas P, F ou J
        if (!in_array($status, ['P', 'F', 'J'])) continue;

        $conn->query("
            INSERT INTO frequencia (aluno_id, aula, status, data_chamada)
            VALUES ('$aluno_id', '$aula', '$status', '$data')
            ON DUPLICATE KEY UPDATE status = '$status'
        ");
    }

    $salvos++;
}

if (count($erros) > 0) {
    echo json_encode([
        "mensagem" => "Chamada salva com avisos ($salvos alunos)",
        "avisos"   => $erros
    ]);
} else {
    echo json_encode([
        "mensagem" => "Chamada salva com sucesso! ($salvos alunos)"
    ]);
}

$conn->close();
?>
