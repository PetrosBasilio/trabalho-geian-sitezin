<?php

$conn = new mysqli("localhost", "root", "", "Escola");

if ($conn->connect_error) {
    die("Erro na conexão");
}

$aluno_id = $_POST['aluno_id'];
$aula = $_POST['aula'];
$status = $_POST['status'];
$data = date("Y-m-d");

$sql = "SELECT * FROM frequencia 
        WHERE aluno_id='$aluno_id'
        AND aula='$aula'
        AND data_chamada='$data'";

$result = $conn->query($sql);

if($result->num_rows > 0){

    $sqlUpdate = "UPDATE frequencia 
                  SET status='$status'
                  WHERE aluno_id='$aluno_id'
                  AND aula='$aula'
                  AND data_chamada='$data'";

    $conn->query($sqlUpdate);

}else{

    $sqlInsert = "INSERT INTO frequencia
    (aluno_id, aula, status, data_chamada)

    VALUES
    ('$aluno_id','$aula','$status','$data')";

    $conn->query($sqlInsert);

}

echo "Salvo";
?>
