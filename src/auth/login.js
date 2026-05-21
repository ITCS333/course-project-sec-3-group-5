const loginForm = document.getElementById("login-form");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const messageContainer = document.getElementById("message-container");

function displayMessage(message, type) {
  messageContainer.textContent = message;
  messageContainer.className = type;
}

function isValidEmail(email) {
  return /\S+@\S+\.\S+/.test(email);
}

function isValidPassword(password) {
  return password.length >= 8;
}

async function handleLogin(event) {
  event.preventDefault();

  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();

  if (!email || !password) {
    displayMessage("Please fill all fields.", "error");
    return;
  }

  if (!isValidEmail(email)) {
    displayMessage("Invalid email format.", "error");
    return;
  }

  if (!isValidPassword(password)) {
    displayMessage("Password must be at least 8 characters.", "error");
    return;
  }

  try {
    const response = await fetch("./api/index.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        email: email,
        password: password
      })
    });

    const result = await response.json();

    if (result.success) {
      displayMessage("Login successful!", "success");

      emailInput.value = "";
      passwordInput.value = "";
    } else {
      displayMessage(result.message, "error");
    }
  } catch (error) {
    displayMessage("Server error.", "error");
  }
}

function setupLoginForm() {
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin);
  }
}

setupLoginForm();
