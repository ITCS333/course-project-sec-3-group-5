async function handleLogin(event) {
  event.preventDefault();

  const email = emailInput.value.trim();
  const password = passwordInput.value.trim();

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
