document.addEventListener("DOMContentLoaded", function () {
    // Scroll Up Button
    const scrollUpBtn = document.getElementById("scrollUpBtn");
    if (scrollUpBtn) {
        window.onscroll = function () {
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                scrollUpBtn.style.display = "block";
            } else {
                scrollUpBtn.style.display = "none";
            }
        };

        scrollUpBtn.onclick = function () {
            window.scrollTo({ top: 0, behavior: "smooth" });
        };
    }

    // Message Timer Functionality
    document.querySelectorAll('.message-footer span[data-delete-at]').forEach(function (timerElement) {
        const deleteAt = new Date(timerElement.dataset.deleteAt);
        const timerId = setInterval(function () {
            const now = new Date();
            if (deleteAt - now <= 0) {
                clearInterval(timerId);
                const messageCard = timerElement.closest('.message-card');
                if (messageCard) {
                    messageCard.classList.add('hidden');
                }
            }
        }, 1000);
    });

    // Auto Show Messages in Sequence
    let messages = document.querySelectorAll('[id^="message-card-"]');
    let currentMessage = 0;
    const messageInterval = 2000;

    function showNextMessage() {
        if (messages[currentMessage]) {
            messages[currentMessage].classList.remove('hidden');
            currentMessage++;
        }
    }

    let messageIntervalID = setInterval(showNextMessage, messageInterval);

    messages.forEach((message) => {
        message.addEventListener('mouseenter', () => clearInterval(messageIntervalID));
        message.addEventListener('mouseleave', () => {
            messageIntervalID = setInterval(showNextMessage, messageInterval);
        });
    });

    // Countdown Timer for Messages
    document.querySelectorAll('[id^="timer-"]').forEach(timer => {
        const deleteAt = new Date(timer.getAttribute('data-delete-at')).getTime();

        const updateTimer = () => {
            const now = new Date().getTime();
            const distance = deleteAt - now;
            if (distance < 0) {
                timer.innerHTML = "Expired soon";
            } else {
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                timer.innerHTML = `Deleting in: ${minutes}m ${seconds}s`;
            }
        };

        setInterval(updateTimer, 1000);
    });

    // Sticky Button on Scroll
    const stickyButton = document.querySelector('.sticky-button');
    if (stickyButton) {
        window.addEventListener('scroll', function () {
            if (window.scrollY > 100) {
                stickyButton.style.display = 'block';
            } else {
                stickyButton.style.display = 'none';
            }
        });
    }

    // Copy Message Text
    window.copyText = function (id) {
        const content = document.getElementById(`message-${id}`).textContent;
        const textarea = document.createElement('textarea');
        textarea.value = content;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    };

    // Toggle Wrap Functionality
    window.toggleWrap = function (id) {
        const content = document.getElementById(`message-${id}`);
        if (content) {
            content.classList.toggle('wrapped');
        }
    };

    // Navbar Toggle on Click
    var navbarToggler = document.querySelector(".navbar-toggler");
    var navbarCollapse = document.querySelector(".navbar-collapse");

    document.querySelectorAll(".nav-link").forEach(link => {
        link.addEventListener("click", () => {
            if (navbarCollapse.classList.contains("show")) {
                navbarToggler.click(); // Close the navbar
            }
        });
    });
});
