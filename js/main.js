document.addEventListener("DOMContentLoaded", function () {
  const reserveBtn = document.getElementById("reserve-btn");
  const countdownDiv = document.getElementById("countdown");
  const timerSpan = document.getElementById("timer");
  const getTicketLink = document.getElementById("get-ticket-link");
  const categorySelect = document.getElementById("ticket-category");
  const selectedCategorySpan = document.getElementById(
    "selected-category-name"
  ); // Corrected ID
  const eventId = new URLSearchParams(window.location.search).get("id");

  if (reserveBtn) {
    reserveBtn.addEventListener("click", function () {
      if (!eventId) {
        alert("Missing event information.");
        return;
      }

      if (!categorySelect || !categorySelect.value) {
        alert("Please choose a ticket category.");
        return;
      }

      reserveBtn.disabled = true;
      reserveBtn.textContent = "Reserving...";

      const payload = new URLSearchParams({
        event_id: eventId,
        category_id: categorySelect.value,
      });

      fetch("reserve_ticket.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: payload.toString(),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            reserveBtn.style.display = "none";
            categorySelect.style.display = "none";
            document.querySelector(
              'label[for="ticket-category"]'
            ).style.display = "none";

            countdownDiv.style.display = "block";
            if (selectedCategorySpan && data.category_name) {
              selectedCategorySpan.textContent = data.category_name;
            }

            // Corrected the URL to use the 'code' parameter
            getTicketLink.href =
              "generate_ticket.php?code=" +
              encodeURIComponent(data.ticket_code);

            let duration = 5 * 60;
            let timer = setInterval(function () {
              let minutes = Math.floor(duration / 60);
              let seconds = duration % 60;

              seconds = seconds < 10 ? "0" + seconds : seconds;
              timerSpan.textContent = minutes + ":" + seconds;

              if (--duration < 0) {
                clearInterval(timer);
                countdownDiv.innerHTML =
                  "<p class='message error'>Your reservation has expired.</p>";
              }
            }, 1000);
          } else {
            alert(data.message || "Unable to reserve ticket.");
            reserveBtn.disabled = false;
            reserveBtn.textContent = "Reserve Ticket";
          }
        })
        .catch(() => {
          alert("An error occurred. Please try again.");
          reserveBtn.disabled = false;
          reserveBtn.textContent = "Reserve Ticket";
        });
    });
  }
});
