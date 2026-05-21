const weekListSection = document.getElementById("week-list-section");

function createWeekArticle(week) {
  const article = document.createElement("article");

  const title = document.createElement("h2");
  title.textContent = week.title;

  const startDate = document.createElement("p");
  startDate.textContent = "Starts on: " + week.start_date;

  const description = document.createElement("p");
  description.textContent = week.description || "";

  const link = document.createElement("a");
  link.href = "details.html?id=" + week.id;
  link.textContent = "View Details & Discussion";

  article.appendChild(title);
  article.appendChild(startDate);
  article.appendChild(description);
  article.appendChild(link);

  return article;
}

async function loadWeeks() {
  try {
    const response = await fetch("./api/index.php");
    const result = await response.json();

    weekListSection.innerHTML = "";

    if (result.success && Array.isArray(result.data) && result.data.length > 0) {
      result.data.forEach(function (week) {
        const article = createWeekArticle(week);
        weekListSection.appendChild(article);
      });
    } else {
      weekListSection.textContent = "No weekly content found.";
    }
  } catch (error) {
    weekListSection.textContent = "Error loading weekly content.";
    console.error(error);
  }
}

loadWeeks();
