const weekListSection = document.getElementById('week-list-section');

const weeks = [
  {
    id: 1,
    title: 'Week 1: Introduction to HTML',
    start_date: '2025-01-13',
    description: 'Learn HTML fundamentals.'
  },
  {
    id: 2,
    title: 'Week 2: CSS Basics',
    start_date: '2025-01-20',
    description: 'Introduction to CSS styling.'
  },
  {
    id: 3,
    title: 'Week 3: JavaScript Basics',
    start_date: '2025-01-27',
    description: 'Learn JavaScript fundamentals.'
  }
];

function createWeekArticle(week) {
  const article = document.createElement('article');

  const h2 = document.createElement('h2');
  h2.textContent = week.title;

  const startDate = document.createElement('p');
  startDate.textContent = 'Starts on: ' + week.start_date;

  const description = document.createElement('p');
  description.textContent = week.description;

  const link = document.createElement('a');
  link.href = 'details.html?id=' + week.id;
  link.textContent = 'View Details & Discussion';

  article.appendChild(h2);
  article.appendChild(startDate);
  article.appendChild(description);
  article.appendChild(link);

  weekListSection.appendChild(article);
}

weeks.forEach(createWeekArticle);
