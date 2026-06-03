function mudarStatus(botao, id){

    if(botao.classList.contains("presente")){

        botao.classList.remove("presente");
        botao.classList.add("falta");
        botao.innerText = "Falta";

        salvar(id, "Falta");

    }else if(botao.classList.contains("falta")){

        botao.classList.remove("falta");
        botao.classList.add("justificada");
        botao.innerText = "Justificada";

        salvar(id, "Justificada");

    }else{

        botao.classList.remove("justificada");
        botao.classList.add("presente");
        botao.innerText = "Presente";

        salvar(id, "Presente");
    }
}

function salvar(id, status){

    fetch("salvar.php", {
        method:"POST",

        headers:{
            "Content-Type":"application/x-www-form-urlencoded"
        },

        body:`id=${id}&status=${status}`
    })
    .then(res => res.text())
    .then(data => {
        console.log(data);
    });

}

const pesquisa = document.getElementById("pesquisa");

pesquisa.addEventListener("keyup", () => {

    const valor = pesquisa.value.toLowerCase();

    const linhas = document.querySelectorAll("#tabela tr");

    linhas.forEach(linha => {

        const nome = linha.querySelector(".nome")
        .innerText
        .toLowerCase();

        if(nome.includes(valor)){
            linha.style.display = "";
        }else{
            linha.style.display = "none";
        }

    });

});
function salvarChamada(){

    const linhas = document.querySelectorAll("#tabela tr");

    const chamadaCompleta = [];

    linhas.forEach(linha => {

        const nome = linha.querySelector(".nome").innerText;

        const botoes = linha.querySelectorAll(".status");

        const aluno = {
            nome: nome,
            aulas: []
        };

        botoes.forEach(botao => {
            aluno.aulas.push(botao.innerText);
        });

        chamadaCompleta.push(aluno);

    });

    fetch("salvar.php", {

        method:"POST",

        headers:{
            "Content-Type":"application/json"
        },

        body: JSON.stringify(chamadaCompleta)

    })

    .then(res => res.text())

    .then(data => {

        alert(data);
        console.log(data);

    })

    .catch(erro => {

        alert("Erro ao salvar!");
        console.log(erro);

    });

}
function carregarChamada(){

    fetch("carregar.php")

    .then(res => res.json())

    .then(dados => {

        dados.forEach(item => {

            const alunoId = item.id;
            const aula = item.aula;
            const status = item.status;

            const linhas =
            document.querySelectorAll("#tabela tr");

            linhas.forEach(linha => {

                const botao =
                linha.querySelector(".status");

                const onclick =
                botao.getAttribute("onclick");

                if(onclick.includes(alunoId)){

                    if(status == "P"){

                        botao.className =
                        "status presente";

                        botao.innerText =
                        "Presente";

                    }

                    if(status == "F"){

                        botao.className =
                        "status falta";

                        botao.innerText =
                        "Falta";

                    }

                    if(status == "J"){

                        botao.className =
                        "status justificada";

                        botao.innerText =
                        "Justificada";

                    }

                }

            });

        });

    });
    
}
