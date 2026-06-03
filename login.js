function entrar(){

    const usuario = document.getElementById("usuario").value;
    const senha = document.getElementById("senha").value;

    if(usuario === "admin" && senha === "1234"){

        window.location.href = "sistema.html";

    }else{

        document.getElementById("erro").innerText =
        "Usuário ou senha incorretos";

    }
}
