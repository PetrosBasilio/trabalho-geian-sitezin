</html>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamada Escolar</title>

    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <h1>📚 Chamada Escolar</h1>

    <input type="text" id="pesquisa" placeholder="Pesquisar aluno...">

    <table>
</a>
            <tr>
                <th>Nº</th>
                <th>Nome</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody id="tabela">
            <?php
            $numero = 1;

            while($aluno = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $numero++ ?></td>

                    <td class="nome">
                        <?= $aluno['nome'] ?>
                    </td>

                    <td>
                        <button 
                            class="status presente"
                            onclick="mudarStatus(this, <?= $aluno['id'] ?>, 1)"
                            Presente
                        </button>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

<script src="script.js"></script>

</body>
</html>
