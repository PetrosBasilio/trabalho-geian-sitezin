<?php

$conn = new mysqli("localhost", "root", "", "Escola");

if ($conn->connect_error) {
    die("Erro na conexão");
}

$data = date("Y-m-d");

$sql = "
SELECT
    alunos.id,
    frequencia.aula,
    frequencia.status

FROM frequencia

INNER JOIN alunos
ON alunos.id = frequencia.aluno_id

WHERE data_chamada='$data'
";

$result = $conn->query($sql);

$dados = [];

while($linha = $result->fetch_assoc()){

    $dados[] = $linha;

}

echo json_encode($dados);

?>
