<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli("localhost", "root", "", "Escola");

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$dados = json_decode(file_get_contents("php://input"), true);

if (!$dados) {
    die("Nenhum dado recebido");
}

$data = date("Y-m-d");

foreach($dados as $aluno){

    $nome = $conn->real_escape_string($aluno['nome']);

    // PROCURA O ALUNO
    $sqlAluno = "SELECT id FROM alunos WHERE nome='$nome'";

    $resultadoAluno = $conn->query($sqlAluno);

    if($resultadoAluno && $resultadoAluno->num_rows > 0){

        $linhaAluno = $resultadoAluno->fetch_assoc();

        $aluno_id = $linhaAluno['id'];

        foreach($aluno['aulas'] as $numeroAula => $status){

            $aula = $numeroAula + 1;

            // CONVERTE STATUS
            if($status == "P"){
                $statusBanco = "P";
            }elseif($status == "F"){
                $statusBanco = "F";
            }else{
                $statusBanco = "J";
            }

            // INSERE NO BANCO
            $sql = "
            INSERT INTO frequencia
            (aluno_id, aula, status, data_chamada)
            VALUES
            ('$aluno_id', '$aula', '$statusBanco', '$data')
            ";

            if(!$conn->query($sql)){
                die("Erro SQL: " . $conn->error);
            }

        }

    } else {

        echo "Aluno não encontrado: " . $nome . "<br>";

    }

}

echo "Chamada salva com sucesso!";
?>
