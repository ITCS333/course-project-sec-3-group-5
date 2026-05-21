/*
  Requirement: Populate the "Weekly Course Breakdown" list page.
*/

// ---------- Element Selection ----------
const weekListSection = document.getElementById('week-list-section');


// ---------- Create Week Article ----------
function createWeekArticle(week) {

    const article = document.createElement('article');

    const title = document.createElement('h2');
    title.textContent = week.title;

    const startDate = document.createElement('p');
    startDate.textContent = 'Starts on: ' + week.start_date;

    const description = document.createElement('p');
    description.textContent = week.description;

    const link = document.createElement('a');
    link.href = 'details.html?id=' + week.id;
    link.textContent = 'View Details & Discussion';

    article.appendChild(title);
    article.appendChild(startDate);
    article.appendChild(description);
    article.appendChild(link);

    return article;
}


// ---------- Load Weeks ----------
function loadWeeks() {

    const weeks = [
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

    weekListSection.innerHTML = '';

    weeks.forEach(function (week) {

        const article = createWeekArticle(week);

        weekListSection.appendChild(article);

    });

}


// ---------- Initial Page Load ----------
loadWeeks();


// ---------- Export Functions For Tests ----------
window.createWeekArticle = createWeekArticle;
window.loadWeeks = loadWeeks;
