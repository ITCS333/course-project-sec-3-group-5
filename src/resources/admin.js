let resources = [];
let editResourceId = null;

// --- Element Selections ---
const resourceForm = document.querySelector("#resource-form");
const resourcesTbody = document.querySelector("#resources-tbody");

const titleInput = document.querySelector("#resource-title");
const descriptionInput = document.querySelector("#resource-description");
const linkInput = document.querySelector("#resource-link");
const submitButton = document.querySelector("#add-resource");

// --- Functions ---
function createResourceRow(resource) {
  const tr = document.createElement("tr");

  tr.innerHTML = `
    <td>${resource.title}</td>
    <td>${resource.description}</td>
    <td><a href="${resource.link}" target="_blank">Open</a></td>
    <td>
      <button class="edit-btn" data-id="${resource.id}">Edit</button>
      <button class="delete-btn" data-id="${resource.id}">Delete</button>
    </td>
  `;

  return tr;
}

function renderTable() {
  resourcesTbody.innerHTML = "";

  resources.forEach(function (resource) {
    const row = createResourceRow(resource);
    resourcesTbody.appendChild(row);
  });
}

async function handleAddResource(event) {
  event.preventDefault();

  const title = titleInput.value.trim();
  const description = descriptionInput.value.trim();
  const link = linkInput.value.trim();

  if (title === "" || link === "") {
    return;
  }

  if (editResourceId) {
    const response = await fetch("./api/index.php", {
      method: "PUT",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify({
        id: editResourceId,
        title: title,
        description: description,
        link: link
      })
    });

    const result = await response.json();

    if (result.success) {
      resources = resources.map(function (resource) {
        if (String(resource.id) === String(editResourceId)) {
          return {
            id: editResourceId,
            title: title,
            description: description,
            link: link
          };
        }
        return resource;
      });

      editResourceId = null;
      submitButton.textContent = "Add Resource";
      resourceForm.reset();
      renderTable();
    }

    return;
  }

  const response = await fetch("./api/index.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({
      title: title,
      description: description,
      link: link
    })
  });

  const result = await response.json();

  if (result.success) {
    const newResource = {
      id: result.id,
      title: title,
      description: description,
      link: link
    };

    resources.push(newResource);
    renderTable();
    resourceForm.reset();
  }
}

async function handleTableClick(event) {
  const target = event.target;

  if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;

    const response = await fetch(`./api/index.php?id=${id}`, {
      method: "DELETE"
    });

    const result = await response.json();

    if (result.success) {
      resources = resources.filter(function (resource) {
        return String(resource.id) !== String(id);
      });

      renderTable();
    }
  }

  if (target.classList.contains("edit-btn")) {
    const id = target.dataset.id;

    const resource = resources.find(function (resource) {
      return String(resource.id) === String(id);
    });

    if (!resource) {
      return;
    }

    editResourceId = id;
    titleInput.value = resource.title;
    descriptionInput.value = resource.description;
    linkInput.value = resource.link;
    submitButton.textContent = "Update Resource";
  }
}

async function loadAndInitialize() {
  const response = await fetch("./api/index.php");
  const result = await response.json();

  resources = result.success ? result.data : [];

  renderTable();

  resourceForm.addEventListener("submit", handleAddResource);
  resourcesTbody.addEventListener("click", handleTableClick);
}

// --- Initial Page Load ---
loadAndInitialize();
