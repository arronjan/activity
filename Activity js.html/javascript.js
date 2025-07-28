const quiz = [
  {
    question: "What is the capital of the Philippines?",
    options: ["Cebu", "Davao", "Manila", "Baguio"],
    answer: "Manila"
  },
  {
    question: "Which planet is known as the Red Planet?",
    options: ["Venus", "Mars", "Jupiter", "Saturn"],
    answer: "Mars"
  },
  {
    question: "What language is primarily used to style web pages?",
    options: ["HTML", "JavaScript", "CSS", "PHP"],
    answer: "CSS"
  }
];

let currentQuestion = 0;
let score = 0;

const questionElement = document.getElementById("question");
const optionsElement = document.getElementById("options");
const feedbackElement = document.getElementById("feedback");
const scoreElement = document.getElementById("score");
const nextButton = document.getElementById("next-btn");

function displayQuestion() {
  const current = quiz[currentQuestion];
  questionElement.textContent = current.question;
  optionsElement.innerHTML = "";
  feedbackElement.textContent = "";
  nextButton.style.display = "none";

  current.options.forEach(option => {
    const li = document.createElement("li");
    li.textContent = option;
    li.onclick = () => checkAnswer(option);
    optionsElement.appendChild(li);
  });

  updateScore();
}

function checkAnswer(selected) {
  const correct = quiz[currentQuestion].answer;

  if (selected === correct) {
    feedbackElement.textContent = "Correct!";
    feedbackElement.style.color = "green";
    score++;
  } else {
    feedbackElement.textContent = `Wrong! Correct answer: ${correct}`;
    feedbackElement.style.color = "red";
  }

  Array.from(optionsElement.children).forEach(li => {
    li.onclick = null; // Disable more clicks
  });

  nextButton.style.display = "inline-block";
  updateScore();
}

function updateScore() {
  scoreElement.textContent = `Score: ${score}/${quiz.length}`;
}

nextButton.onclick = () => {
  currentQuestion++;
  if (currentQuestion < quiz.length) {
    displayQuestion();
  } else {
    showFinalScore();
  }
};

function showFinalScore() {
  questionElement.textContent = "Quiz Completed!";
  optionsElement.innerHTML = "";
  feedbackElement.textContent = "";

  let message = "You can do better!";
  if (score === quiz.length) {
    message = "Great job!";
  } else if (score >= quiz.length / 2) {
    message = "Well done!";
  }

  scoreElement.textContent = `Final Score: ${score}/${quiz.length}`;
  nextButton.style.display = "none";
  feedbackElement.textContent = message;
  feedbackElement.style.color = "blue";
}

// Start the quiz
displayQuestion();
