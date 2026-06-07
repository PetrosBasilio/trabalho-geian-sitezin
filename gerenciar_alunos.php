<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$conn = new mysqli("localhost", "root", "", "Escola");
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["erro" => "Conexão falhou: " . $conn->connect_error]);
    exit;
}

$acao  = $_GET['acao']  ?? '';
$turma = $_GET['turma'] ?? '2D';
$turma = $conn->real_escape_string(trim($turma));

// Garante que a turma existe
$conn->query("INSERT IGNORE INTO turmas (nome) VALUES ('$turma')");

// =============================================
// AÇÃO: listar — retorna alunos da turma
// =============================================
if ($acao === 'listar') {
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
    echo json_encode(["alunos" => $list]);
    exit;
}

// =============================================
// AÇÃO: adicionar — insere novo aluno
// =============================================
if ($acao === 'adicionar') {
    $body = json_decode(file_get_contents("php://input"), true);
    $nome = $conn->real_escape_string(trim($body['nome'] ?? ''));

    if (!$nome) {
        echo json_encode(["erro" => "Nome inválido"]);
        exit;
    }

    // Verifica se já existe
    $check = $conn->query("
        SELECT a.id FROM alunos a
        INNER JOIN turmas t ON t.id = a.turma_id
        WHERE a.nome = '$nome' AND t.nome = '$turma' AND a.ativo = 1
    ");

    if ($check && $check->num_rows > 0) {
        echo json_encode(["erro" => "Aluno '$nome' já existe na turma $turma"]);
        exit;
    }

    $ins = $conn->query("
        INSERT INTO alunos (nome, turma_id)
        SELECT '$nome', id FROM turmas WHERE nome = '$turma' LIMIT 1
    ");

    if ($ins) {
        echo json_encode([
            "mensagem" => "Aluno '$nome' adicionado com sucesso!",
            "id"       => $conn->insert_id
        ]);
    } else {
        echo json_encode(["erro" => "Erro ao adicionar: " . $conn->error]);
    }
    exit;
}

// =============================================
// AÇÃO: remover — desativa o aluno (soft delete)
// =============================================
if ($acao === 'remover') {
    $body     = json_decode(file_get_contents("php://input"), true);
    $aluno_id = intval($body['id'] ?? 0);

    if (!$aluno_id) {
        echo json_encode(["erro" => "ID inválido"]);
        exit;
    }

    // Soft delete — mantém histórico de frequência
    $upd = $conn->query("UPDATE alunos SET ativo = 0 WHERE id = $aluno_id");

    if ($upd) {
        echo json_encode(["mensagem" => "Aluno removido com sucesso!"]);
    } else {
        echo json_encode(["erro" => "Erro ao remover: " . $conn->error]);
    }
    exit;
}

// =============================================
// AÇÃO: sincronizar — sincroniza lista do localStorage com o banco
// =============================================
if ($acao === 'sincronizar') {
    $body  = json_decode(file_get_contents("php://input"), true);
    $nomes = $body['alunos'] ?? [];

    if (!is_array($nomes)) {
        echo json_encode(["erro" => "Lista inválida"]);
        exit;
    }

    $adicionados = 0;

    foreach ($nomes as $nome) {
        $nome = $conn->real_escape_string(trim($nome));
        if (!$nome) continue;

        // Insere só se não existir
        $check = $conn->query("
            SELECT a.id FROM alunos a
            INNER JOIN turmas t ON t.id = a.turma_id
            WHERE a.nome = '$nome' AND t.nome = '$turma' AND a.ativo = 1
        ");

        if (!$check || $check->num_rows === 0) {
            $conn->query("
                INSERT INTO alunos (nome, turma_id)
                SELECT '$nome', id FROM turmas WHERE nome = '$turma' LIMIT 1
            ");
            $adicionados++;
        }
    }

    echo json_encode([
        "mensagem"    => "Sincronização concluída!",
        "adicionados" => $adicionados
    ]);
    exit;
}

echo json_encode(["erro" => "Ação inválida"]);
$conn->close();
?>
