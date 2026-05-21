let users = [];
const currentAdminId = 1;

const userTableBody     = document.getElementById("user-table-body");
const addUserForm       = document.getElementById("add-user-form");
const changePasswordForm = document.getElementById("password-form");
const searchInput       = document.getElementById("search-input");
const tableHeaders      = document.querySelectorAll("#user-table thead th");

function createUserRow(user) {
  const tr = document.createElement("tr");

  const nameTd  = document.createElement("td");
  nameTd.textContent = user.name;

  const emailTd = document.createElement("td");
  emailTd.textContent = user.email;

  const adminTd = document.createElement("td");
  adminTd.textContent = Number(user.is_admin) === 1 ? "Yes" : "No";

  const actionsTd = document.createElement("td");

  const editBtn = document.createElement("button");
  editBtn.className = "edit-btn";
  editBtn.textContent = "Edit";
  editBtn.dataset.id = String(user.id);

  const delBtn = document.createElement("button");
  delBtn.className = "delete-btn";
  delBtn.textContent = "Delete";
  delBtn.dataset.id = String(user.id);

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(delBtn);

  tr.appendChild(nameTd);
  tr.appendChild(emailTd);
  tr.appendChild(adminTd);
  tr.appendChild(actionsTd);

  return tr;
}

function renderTable(userArray) {
  userTableBody.innerHTML = "";
  userArray.forEach(u => userTableBody.appendChild(createUserRow(u)));
}

function handleChangePassword(event) {
  event.preventDefault();
  const current = document.getElementById("current-password").value;
  const next    = document.getElementById("new-password").value;
  const confirm = document.getElementById("confirm-password").value;

  if (next !== confirm) {
    alert("Passwords do not match.");
    return;
  }
  if (next.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  fetch("../api/index.php?action=change_password", {
    method:  "POST",
    headers: { "Content-Type": "application/json" },
    body:    JSON.stringify({
      id:               currentAdminId,
      current_password: current,
      new_password:     next,
    }),
  })
    .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
    .then(({ ok, body }) => {
      if (ok && body.success) {
        alert("Password updated successfully!");
      } else {
        alert(body.message || "Failed to update password.");
      }
    })
    .catch(err => alert("Network error: " + err.message));

  document.getElementById("current-password").value = "";
  document.getElementById("new-password").value     = "";
  document.getElementById("confirm-password").value = "";
}

function handleAddUser(event) {
  event.preventDefault();
  const name     = document.getElementById("user-name").value.trim();
  const email    = document.getElementById("user-email").value.trim();
  const password = document.getElementById("default-password").value;
  const isAdmin  = Number(document.getElementById("is-admin").value);

  if (!name || !email || !password) {
    alert("Please fill out all required fields.");
    return;
  }
  if (password.length < 8) {
    alert("Password must be at least 8 characters.");
    return;
  }

  fetch("../api/index.php", {
    method:  "POST",
    headers: { "Content-Type": "application/json" },
    body:    JSON.stringify({ name, email, password, is_admin: isAdmin }),
  })
    .then(r => r.json().then(j => ({ status: r.status, body: j })))
    .then(({ status, body }) => {
      if (status === 201) {
        addUserForm.reset();
        loadUsersAndInitialize();
      } else {
        alert(body.message || "Failed to add user.");
      }
    })
    .catch(err => alert("Network error: " + err.message));
}

function handleTableClick(event) {
  const target = event.target;
  if (!target) return;

  if (target.classList && target.classList.contains("delete-btn")) {
    const id = target.dataset.id;
    if (!confirm("Delete this user?")) return;
    fetch("../api/index.php?id=" + id, { method: "DELETE" })
      .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
      .then(({ ok, body }) => {
        if (ok && body.success) {
          users = users.filter(u => String(u.id) !== String(id));
          renderTable(users);
        } else {
          alert(body.message || "Failed to delete user.");
        }
      })
      .catch(err => alert("Network error: " + err.message));
    return;
  }

  if (target.classList && target.classList.contains("edit-btn")) {
    const id   = target.dataset.id;
    const user = users.find(u => String(u.id) === String(id));
    if (!user) return;
    const newName = prompt("New name:", user.name);
    if (newName === null) return;
    fetch("../api/index.php", {
      method:  "PUT",
      headers: { "Content-Type": "application/json" },
      body:    JSON.stringify({ id: Number(id), name: newName }),
    })
      .then(r => r.json().then(j => ({ ok: r.ok, body: j })))
      .then(({ ok, body }) => {
        if (ok && body.success) {
          loadUsersAndInitialize();
        } else {
          alert(body.message || "Failed to update user.");
        }
      })
      .catch(err => alert("Network error: " + err.message));
  }
}

function handleSearch(event) {
  const term = searchInput.value.toLowerCase();
  if (!term) {
    renderTable(users);
    return;
  }
  const filtered = users.filter(u =>
    (u.name  || "").toLowerCase().includes(term) ||
    (u.email || "").toLowerCase().includes(term)
  );
  renderTable(filtered);
}

function handleSort(event) {
  const th  = event.currentTarget;
  const idx = th.cellIndex;
  const map = { 0: "name", 1: "email", 2: "is_admin" };
  const key = map[idx];
  if (!key) return;

  const dir = th.dataset.sortDir === "asc" ? "desc" : "asc";
  th.dataset.sortDir = dir;

  users.sort((a, b) => {
    let cmp;
    if (key === "is_admin") {
      cmp = Number(a.is_admin) - Number(b.is_admin);
    } else {
      cmp = String(a[key]).localeCompare(String(b[key]));
    }
    return dir === "asc" ? cmp : -cmp;
  });

  renderTable(users);
}

async function loadUsersAndInitialize() {
  try {
    const response = await fetch("../api/index.php");
    if (!response.ok) {
      console.error("Failed to load users:", response.status);
      alert("Failed to load users.");
      return;
    }
    const json = await response.json();
    users = json.data || [];
    renderTable(users);
  } catch (err) {
    console.error(err);
    alert("Network error loading users.");
  }

  if (!loadUsersAndInitialize._listenersAttached) {
    if (changePasswordForm) changePasswordForm.addEventListener("submit", handleChangePassword);
    if (addUserForm)        addUserForm.addEventListener("submit", handleAddUser);
    if (userTableBody)      userTableBody.addEventListener("click", handleTableClick);
    if (searchInput)        searchInput.addEventListener("input", handleSearch);
    if (tableHeaders)       tableHeaders.forEach(th => th.addEventListener("click", handleSort));
    loadUsersAndInitialize._listenersAttached = true;
  }
}

loadUsersAndInitialize();
