const weekListSection = document.getElementById('week-list-section');

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

async function loadWeeks() {

    const weeks = [
        {
            id: 1,
            title: 'Week 1: HTML Basics',
            start_date: '2026-05-21',
            description: 'Introduction to HTML structure.'
        },
        {
            id: 2,
            title: 'Week 2: CSS Basics',
            start_date: '2026-05-28',
            description: 'Learn CSS styling and layouts.'
        }
    ];

    weekListSection.innerHTML = '';

    weeks.forEach(function(week) {
        const article = createWeekArticle(week);
        weekListSection.appendChild(article);
    });
}

loadWeeks();
