function togglePassword() {
    const pass = document.getElementById("password");
    if (pass.type === "password") {
        pass.type = "text";
    } else {
        pass.type = "password";
    }
}

function login() {
    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;
    const errorMsg = document.getElementById("errorMsg");

    if (email === "" || password === "") {
        errorMsg.style.color = "red";
        errorMsg.innerText = "Email dan password harus diisi!";
    } else if (!email.includes("@")) {
        errorMsg.style.color = "red";
        errorMsg.innerText = "Format email tidak valid!";
    } else {
        errorMsg.style.color = "green";
        errorMsg.innerText = "Login berhasil!";
    }
}