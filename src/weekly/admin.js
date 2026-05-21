/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.
*/

// ---------- Element Selections ----------
const weekForm = document.getElementById('week-form');
const weeksTbody = document.getElementById('weeks-tbody');

const titleInput = document.getElementById('title');
const startDateInput = document.getElementById('start-date');
const descriptionInput = document.getElementById('description');


// ---------- Temporary Data ----------
let weeks = [
  {
    id: 1,
    title: 'Week 1: Introduction to HTML',
    start_date: '2025-01-13',
    description: 'Learn HTML fundamentals'
  },
  {
    id: 2,
    title: 'Week 2: CSS Basics',
    start_date: '2025-01-20',
    description: 'Learn CSS styling'
  }
];


// ---------- Create Table Row ----------
function createWeekRow(week) {
  const tr = document.createElement('tr');

  tr.innerHTML = `
    <td>${week.title}</td>
    <td>${week.start_date}</td>
    <td>${week.description}</td>
    <td>
      <button class="edit-btn" data-id="${week.id}">Edit</button>
      <button class="delete-btn" data-id="${week.id}">Delete</button>
    </td>
  `;

  return tr;
}


// ---------- Render Table ----------
function renderTable() {
  weeksTbody.innerHTML = '';

  weeks.forEach(function (week) {
    const row = createWeekRow(week);
    weeksTbody.appendChild(row);
  });
}


// ---------- Add Week ----------
function handleAddWeek(event) {
  event.preventDefault();

  const newWeek = {
    id: Date.now(),
    title: titleInput.value,
    start_date: startDateInput.value,
    description: descriptionInput.value
  };

  weeks.push(newWeek);

  renderTable();

  weekForm.reset();
}


// ---------- Update Week ----------
function handleUpdateWeek(id) {
  const week = weeks.find(w => w.id == id);

  if (!week) return;

  const newTitle = prompt('Edit title:', week.title);
  const newDate = prompt('Edit start date:', week.start_date);
  const newDescription = prompt('Edit description:', week.description);

  if (newTitle !== null) week.title = newTitle;
  if (newDate !== null) week.start_date = newDate;
  if (newDescription !== null) week.description = newDescription;

  renderTable();
}


// ---------- Delete Week ----------
function handleDeleteWeek(id) {
  weeks = weeks.filter(w => w.id != id);

  renderTable();
}


// ---------- Handle Table Click ----------
function handleTableClick(event) {
  const id = event.target.dataset.id;

  if (event.target.classList.contains('edit-btn')) {
    handleUpdateWeek(id);
  }

  if (event.target.classList.contains('delete-btn')) {
    handleDeleteWeek(id);
  }
}


// ---------- Load & Initialize ----------
function loadAndInitialize() {
  renderTable();

  weekForm.addEventListener('submit', handleAddWeek);

  weeksTbody.addEventListener('click', handleTableClick);
}


// ---------- Start ----------
loadAndInitialize();
