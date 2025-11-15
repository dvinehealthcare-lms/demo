<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guest') {
    header("Location: index.php");
    exit();
}
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
$email = $_SESSION['email'];
$message = "";
$redirectToTest2 = false;
$stayOnQuiz = false;

$conn = new mysqli("localhost", "dvinehea_lms", " j6Z5W9p;+Tj3Ts", "dvinehea_dvine.db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Check status
$test1_done = $test2_done = false;
$test1_score = $test2_score = null;
$test1_percent = $test2_percent = null;

$check = $conn->prepare("SELECT test1_score, test1_percentage, test2_score, test2_percentage FROM quiz_results WHERE user_email=?");
$check->bind_param("s", $email);
$check->execute();
$check->bind_result($t1_score, $t1_percent, $t2_score, $t2_percent);
$check->fetch();
$check->close();

if ($t1_score !== null) {
    $test1_done = true;
    $test1_score = $t1_score;
    $test1_percent = $t1_percent;
}
if ($t2_score !== null) {
    $test2_done = true;
    $test2_score = $t2_score;
    $test2_percent = $t2_percent;
}
$both_done = $test1_done && $test2_done;

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$both_done) {
    $test = $_POST["test"];
    $score = $_POST["score"];
    $percentage = $_POST["percentage"];
    $date = date("Y-m-d");

    $scoreCol = $test . "_score";
    $percentCol = $test . "_percentage";
    $dateCol = $test . "_date";

    $check = $conn->prepare("SELECT id FROM quiz_results WHERE user_email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    $exists = $check->num_rows > 0;
    $check->close();

    if ($exists) {
        $check = $conn->prepare("SELECT $scoreCol FROM quiz_results WHERE user_email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->bind_result($existingScore);
        $check->fetch();
        $check->close();

        if ($existingScore !== null) {
            $message = "⚠️ You already submitted $test.";
        } else {
            $update = $conn->prepare("UPDATE quiz_results SET $scoreCol=?, $percentCol=?, $dateCol=? WHERE user_email=?");
            $update->bind_param("ddss", $score, $percentage, $date, $email);
            $update->execute();
            $message = "✅ $test submitted successfully.";
        }
    } else {
        $insert = $conn->prepare("INSERT INTO quiz_results (user_email, $scoreCol, $percentCol, $dateCol) VALUES (?, ?, ?, ?)");
        $insert->bind_param("sdds", $email, $score, $percentage, $date);
        $insert->execute();
        $message = "✅ $test submitted successfully.";
    }

    // Refresh test status
    $check = $conn->prepare("SELECT test1_score, test1_percentage, test2_score, test2_percentage FROM quiz_results WHERE user_email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->bind_result($t1_score, $t1_percent, $t2_score, $t2_percent);
    $check->fetch();
    $check->close();

    if ($t1_score !== null) {
        $test1_done = true;
        $test1_score = $t1_score;
        $test1_percent = $t1_percent;
    }
    if ($t2_score !== null) {
        $test2_done = true;
        $test2_score = $t2_score;
        $test2_percent = $t2_percent;
    }
    $both_done = $test1_done && $test2_done;

    if ($test === 'test1' && $test1_done && !$test2_done) $redirectToTest2 = true;
    if ($test === 'test2' && $test2_done) $stayOnQuiz = true;
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Guest Quiz</title>
        <link rel="icon" type="image/png" href="D'Vine Healthcare - Logo - Final-03.png">
        <!-- Favicon (Logo in Title Bar) -->
    <link rel="icon" type="image/png" href="D'Vine Healthcare - Logo - Final-03.png">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
    /* Global Styles */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f4f6f9;
        color: #333;
        display: flex;
        min-height: 100vh;
        flex-direction: row;
    }

    /* Sidebar */
    .sidebar {
        width: 220px;
        background-color: #80b64e;
        color: white;
        padding-top: 20px;
        flex-shrink: 0;
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
    }

    .sidebar a {
        display: block;
        padding: 15px 20px;
        color: white;
        text-decoration: none;
        font-weight: 500;
        border-bottom: 1px solid #6d9f42;
        transition: background 0.3s, opacity 0.3s;
    }

    .sidebar a:hover {
        background-color: #a1d064;
        color: #000;
    }

    /* Style for disabled links */
    .disabled-link {
        pointer-events: none;
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Mobile Header */
    .mobile-header {
        background-color: #80b64e;
        color: white;
        padding: 15px 20px;
        font-size: 18px;
        font-weight: bold;
        text-align: left;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
        display: none;
    }

    /* Main Content */
    .main {
        flex-grow: 1;
        padding: 30px;
        margin-left: 220px;
    }

    .container {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        padding: 30px;
        max-width: 750px;
        margin: auto;
    }

    h2 {
        text-align: center;
        color: #2d358b;
        margin-bottom: 20px;
    }

    label {
        margin-top: 15px;
        font-weight: 500;
        display: block;
    }

    select,
    button {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        margin: 12px 0;
        border-radius: 8px;
        border: 1px solid #ccc;
    }

    .question {
        background: #f5fbe9;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 8px;
        border-left: 5px solid #80b64e;
    }

    .message {
        text-align: center;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .success {
        color: #2e7d32;
    }

    .error {
        color: #c62828;
    }

    button {
        background-color: #80b64e;
        color: white;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    button:hover {
        background-color: #6aa63c;
    }

    .result-box {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        font-size: 16px;
    }

    .result-box ul {
        list-style: none;
        padding-left: 0;
    }

    .result-box li {
        margin: 10px 0;
        padding: 10px;
        background: #f0f7ea;
        border-radius: 5px;
    }

    /* Timer styles */
    .timer-container {
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.2em;
        font-weight: bold;
        color: #2d358b;
    }

    /* Responsive */
    @media (max-width: 768px) {
        body {
            flex-direction: column;
        }

        .sidebar {
            width: 100%;
            height: 60px;
            display: flex;
            flex-direction: row;
            justify-content: space-around;
            position: fixed;
            top: auto;
            bottom: 0;
            left: 0;
            z-index: 999;
            padding: 0;
        }

        .sidebar a {
            flex: 1;
            text-align: center;
            padding: 15px 0;
            font-size: 16px;
            border-bottom: none;
        }

        .main {
            margin-left: 0;
            padding-top: 20px;
            padding-bottom: 100px;
        }

        .mobile-header {
            display: block;
        }
    }
</style>
</head>

<body>

<header class="mobile-header">
    Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>
</header>

<div class="sidebar">
    <a href="#home" id="homeLink" onclick="showSection('home')"><i class="fas fa-home"></i> Home</a>
    <a href="#quiz" onclick="alert('📌 No. of Anatomy Questions = 75\n📌 No. of Aptitude Questions = 25\n⏱ Duration = 60 mins (Anatomy) / 25 mins (Aptitude)\n🔁 No. of Attempts = 1'); showSection('quiz')">
        <i class="fas fa-pen-alt"></i> Test
    </a>
    <a href="?logout=true" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <div id="homeSection" class="container">
        <h2>Welcome to D'vine Healthcare</h2>
        <p style="text-align:center;">Go to the test section to take the test.</p>
    </div>

    <div id="quizSection" class="container" style="display:none;">
        <h2>Take Quiz</h2>
        <?php if ($message): ?>
            <div class="message <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if (!$both_done): ?>
        <div id="timerDisplay" class="timer-container" style="display:none;">Time Left: <span id="time"></span></div>
        <form method="post" id="quizForm" onsubmit="return calculateScore()">
            <label>Select Test:</label>
            <select name="test" id="test" onchange="loadQuestions(); startTimer(); updateLinkState();" required>
                <?php if (!$test1_done): ?>
                    <option value="test1">Anatomy</option>
                <?php elseif ($test1_done && !$test2_done): ?>
                    <option value="test2">Aptitude</option>
                <?php endif; ?>
            </select>
            <div id="questionsContainer"></div>
            <input type="hidden" name="score" id="scoreInput" />
            <input type="hidden" name="percentage" id="percentageInput" />
            <button type="submit" id="submitButton">Submit Quiz</button>
        </form>
        <?php endif; ?>

        <?php if ($test1_done || $test2_done): ?>
            <div class="result-box">
            <center><h3>📊 Your Quiz Results:</h3></center><br>
                <ul>
                    <?php if ($test1_done): ?>
                        <li>🧪 Test 1: Score = <?= $test1_score ?>/75, Percentage = <?= $test1_percent ?>%</li><br>
                    <?php endif; ?>
                    <?php if ($test2_done): ?>
                        <li>🧪 Test 2: Score = <?= $test2_score ?>/25, Percentage = <?= $test2_percent ?>%</li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// This variable is set by PHP to know if all tests are done.
const bothTestsDone = <?php echo json_encode($both_done); ?>;

// Fisher-Yates (Knuth) shuffle algorithm
function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]]; // Swap elements
    }
}

const quizData = {
    test1: [
        { q: "What part of the neuron receives impulses from other neurons?", options: ["Axon", "Myelin", "Dendrite","Soma"], answer: "Dendrite" },
        { q: "Which lobe of the brain is responsible for vision?", options: [" Temporal", " Parietal", "Occipital ", "Frontal"], answer: "Occipital " },
        { q: "Which blood vessels carry oxygen-rich blood away from the heart?", options: ["Veins", "Arteries", " Capillaries", "   Venules"], answer: "Arteries" },
        { q: " What is the pacemaker of the heart?", options: ["AV node", "SA node", "Bundle of His", "Purkinje fibers"], answer: "SA node" },
        { q: " Where does gas exchange occur in the lungs?", options: ["Bronchi", "Trachea", "Alveoli ", "Bronchioles"], answer: "Alveoli " },
        { q: " What is the muscle responsible for breathing?", options: ["Intercostal", "Sternum", "Diaphragm ", "Larynx"], answer: "Diaphragm " },
        { q: "Which organ produces bile?", options: ["Pancreas", " Gallbladder", "Liver", "Stomach"], answer: "Liver" },
        { q: "Where does most nutrient absorption occur?", options: [" Large intestine", " Duodenum", "Stomach", "Small intestine"], answer: "Small intestine" },
        { q: "Which organ filters blood and helps fight infection?", options: [" Liver", "Kidney", "Spleen", "Lung"], answer: "Spleen" },
        { q: " What type of white blood cell produces antibodies?", options: [" T cells", "Neutrophils", " B cells ", "Monocytes"], answer: " B cells " },
        { q: "How many bones are in the adult human body?", options: ["205", "206", "210", "201"], answer: "206" },
        { q: "What type of bone is the femur?", options: ["Short", "Flat", "Irregular", "Long"], answer: "Long" },
        { q: " What type of muscle is found in the walls of hollow organs?", options: ["Skeletal", "Cardiac", "Smooth ", " Voluntary"], answer: "Smooth " },
        { q: " Which protein is essential for muscle contraction?", options: ["Myoglobin", "Actin", "Keratin", "Hemoglobin"], answer: "Actin" },
        { q: " What is the functional unit of the kidney?", options: ["Nephron", "Glomerulus", "Ureter", "Alveolus"], answer: "Nephron" },
        { q: " Which organ stores urine before excretion?", options: ["Urethra", "Kidney", "Bladder", "Prostate"], answer: "Bladder" },
        { q: " Which gland is called the “master gland”?", options: ["Thyroid", "Pituitary", "Adrenal", "Pineal"], answer: "Pituitary" },
        { q: " Insulin is produced by:", options: ["Liver", "Pancreas ", "Kidney", "Gallbladder"], answer: "Pancreas " },
        { q: " Where does fertilization usually occur?", options: ["Uterus", " Ovary", "Fallopian tube", "Vagina"], answer: "Fallopian tube" },
        { q: "Which hormone regulates the menstrual cycle?", options: [" Testosterone", "Estrogen ", "Insulin", "Oxytocin"], answer: "Estrogen " },
        { q: " What is the outermost layer of skin called?", options: ["Dermis", " Epidermis", " Hypodermis", "Subcutaneous layer"], answer: " Epidermis" },   
        { q: "What pigment gives skin its color?", options: ["Hemoglobin", "Melanin", "Collagen", "Keratin"], answer: "Melanin" },
        { q: " What structure in the eye focuses light on the retina?", options: ["Iris", "Cornea", "Lens", "Sclera"], answer: "Lens" },
        { q: " The cochlea is involved in:", options: ["Balance", "Hearing", " Smell", " Taste"], answer: "Hearing" },
        { q: "Which glial cells form the myelin sheath in the central nervous system?", options: ["Schwann cells", " Astrocytes", "Oligodendrocytes", "Microglia"], answer: "Oligodendrocytes" }, 
        { q: " The blood-brain barrier is mainly composed of:", options: ["Endothelial cells and tight junctions", " Astrocyte end-feet and capillary endothelial cells", "Myelin and cerebrospinal fluid", "Neurons and Schwann cells"], answer: " Astrocyte end-feet and capillary endothelial cells" },
        { q: "Which of the following is true about the cardiac conduction system?", options: ["The AV node initiates the heartbeat", " The SA node sets the pace of the heart", "Purkinje fibers delay impulse transmission", "The bundle of His contracts the ventricles"], answer: " The SA node sets the pace of the heart" },
        { q: "What causes the second heart sound (S2)?", options: ["Opening of AV valves", "Contraction of ventricles", "Closing of mitral valve", "Closing of aortic and pulmonary valves"], answer: "Closing of aortic and pulmonary valves" },
        { q: "The majority of carbon dioxide is transported in blood as:", options: ["Dissolved CO₂", "Bicarbonate ions", "Carbaminohemoglobin", "Bound to albumin"], answer: "Bicarbonate ions" },   
        { q: "Which part of the brain regulates the respiratory rhythm?", options: ["Cerebellum", "Medulla oblongata", "Thalamus", " Pons"], answer: "Medulla oblongata" },
        { q: " Which enzyme is secreted in an inactive form to prevent self-digestion?", options: ["Amylase", "Lipase", "Pepsinogen", "Maltase"], answer: "Pepsinogen" },
        { q: "The hormone secretin mainly stimulates:", options: ["Gastric acid secretion", "Pancreatic bicarbonate secretion", " Bile production", " Pepsinogen activation"], answer: "Pancreatic bicarbonate secretion" },
        { q: "Which part of the nephron is impermeable to water?", options: [" Ascending limb of the loop of Henle", "Proximal convoluted tubule", "Collecting duct", "Descending limb of Henle"], answer: " Ascending limb of the loop of Henle" },   
        { q: " Aldosterone acts on which part of the nephron?", options: ["Glomerulus", "Proximal tubule", "Distal convoluted tubule and collecting duct", " Loop of Henle"], answer: "Distal convoluted tubule and collecting duct" },
        { q: "The functional unit of skeletal muscle is:", options: ["Fascicle", " Myofibril", "Myosin", " Sarcomere"], answer: " Sarcomere" },
        { q: "What initiates the power stroke in muscle contraction?", options: [" Release of ADP and Pi from myosin head", "Binding of ATP to myosin", " Calcium release from sarcoplasmic reticulum", "Troponin binding to actin"], answer: " Release of ADP and Pi from myosin head" },         
        { q: "Which bone is the keystone of the cranial floor?", options: ["Occipital", "Temporal", "Sphenoid", "Parietal"], answer: "Sphenoid" },
        { q: "Osteoclasts are primarily responsible for:", options: ["Bone formation", "Bone resorption", "Collagen synthesis", " Mineral storage"], answer: "Bone resorption" },   
        { q: "Which hormone increases blood calcium by stimulating osteoclast activity?", options: [" Calcitonin", " Insulin", "Parathyroid hormone", "Aldosterone"], answer: "Parathyroid hormone" },
        { q: "The adrenal medulla secretes:", options: ["Cortisol and aldosterone", " Epinephrine and norepinephrine", "ADH and oxytocin", "Glucagon and insulin"], answer: " Epinephrine and norepinephrine" },
        { q: " Which cells are responsible for cell-mediated immunity?", options: ["B lymphocytes", "T lymphocytes", " Macrophages", "Neutrophils"], answer: "T lymphocytes" },   
        { q: "The thymus is most active:", options: ["During childhood", " During adulthood", " After puberty", "In old age"], answer: "During childhood" },
        { q: "Which layer of the skin contains blood vessels?", options: ["Epidermis", " Dermis", " Stratum corneum", "Stratum lucidum"], answer: " Dermis" },
        { q: " What type of glands are responsible for thermoregulation through sweat?", options: [" Eccrine glands", "Apocrine glands", " Sebaceous glands", "Ceruminous glands"], answer: " Eccrine glands" },     
        { q: "Which structure produces progesterone after ovulation?", options: ["Follicle", "Corpus luteum", "Endometrium", "Oviduct"], answer: "Corpus luteum" },
        { q: "Sertoli cells in the testes:", options: ["Produce testosterone", "Support and nourish developing sperm", "Secrete estrogen", "Store sperm"], answer: "Support and nourish developing sperm" },   
        { q: "Rod cells in the retina are responsible for:", options: ["Color vision", "Central vision", "Dim light (night) vision", "Detecting motion"], answer: "Dim light (night) vision" },
        { q: "The organ of Corti is found in:", options: ["Semicircular canals", "Tympanic membrane", "Cochlear duct", " D. Cochlea"], answer: " D. Cochlea" },
        { q: " Which of the following is a true endocrine and exocrine gland?", options: [" Thyroid", "Adrenal", "Pancreas", "Parathyroid"], answer: "Pancreas" },     
        { q: "What does ICD stand for in medical coding?", options: ["Internal Care Directive", "International Classification of Disorders", " International Classification of Diseases", "Inpatient Coding Directory"], answer: " International Classification of Diseases" },      
        { q: "Which organization is responsible for maintaining ICD-10-CM in the United States?", options: [" World Health Organization", " American Medical Association", " CMS and NCHS ", "FDA"], answer: " CMS and NCHS " },
        { q: "ICD-10-CM codes are used to represent:", options: [" Surgical procedures", " Physician credentials", " Diagnoses and conditions", "Drug usage"], answer: " Diagnoses and conditions" },   
        { q: "In ICD-10-CM, which placeholder character is used when a 7th character extension is required but a code has fewer than 6 characters?", options: ["X", "Y", "Z", "0"], answer: "X" },
        { q: " What does the “Excludes1” note in the ICD-10-CM indicate?", options: ["Codes that must be reported together", "A condition that is not included and should never be coded with the listed code", "Optional coding", " Required separate diagnosis"], answer: "A condition that is not included and should never be coded with the listed code" },
        { q: "When coding a diagnosis, which should be selected?", options: ["The most general term", "The default code", "The unspecified code", "The most specific code available"], answer: "The most specific code available" },
        { q: "Which ICD-10-CM chapter includes diseases of the circulatory system?", options: ["Chapter 3", "Chapter 9 ", " Chapter 11", " Chapter 5"], answer: "Chapter 9 " },
        { q: "When a patient is being treated for sequela (late effect), which code is sequenced first?", options: ["Sequela code", "Acute condition", " The current effect or condition ", "Original injury or illness"], answer: " The current effect or condition " },           
        { q: "What is the purpose of the 7th character in ICD-10-CM codes?", options: [" To indicate primary or secondary diagnosis", " To indicate the severity of the illness", " To provide extension information like encounter type ", "To indicate provider details"], answer: " To provide extension information like encounter type " },    
        { q: "How often is the ICD-10-CM code set updated in the U.S.?", options: ["Monthly", "Bi-annually", "Annually ", "Every 5 years"], answer: "Annually " },    
        { q: "What does the prefix neuro- refer to?", options: ["Blood", "Nerve", "Kidney", "Liver"], answer: "Nerve" },
        { q: "Which pathology is a degenerative disease of the brain?", options: ["Nephritis", "Hepatitis", "Alzheimer’s disease", "Dermatitis"], answer: "Alzheimer’s disease" },
        { q: "The suffix -emia refers to:", options: ["Condition of urine", "Condition of blood", "Condition of nerves", "Condition of muscles"], answer: "Condition of blood" },
        { q: "Hypertension is a pathology involving:", options: ["Low blood sugar", "High blood pressure", "Poor oxygen supply", "Heart failure"], answer: "High blood pressure" },
        { q: "What does the suffix -pnea mean?", options: ["Breathing", "Coughing", "Voice", "Chest"], answer: "Breathing" },
        { q: "Which is a common pathology of the respiratory system?", options: ["Asthma", "Nephrosis", "Gastritis", "Myalgia"], answer: "Asthma" },
        { q: "What does the prefix gastro- refer to?", options: ["Liver", "Intestine", "Stomach", "Tongue"], answer: "Stomach" },
        { q: "Inflammation of the liver is called:", options: ["Nephritis", "Gastritis", "Hepatitis", "Colitis"], answer: "Hepatitis" },
        { q: "What does the suffix -uria mean?", options: ["Inflammation", "Urine condition", "Pain", "Stone"], answer: "Urine condition" },
        { q: "Nephritis affects which organ?", options: ["Lung", "Brain", "Kidney", "Heart"], answer: "Kidney" },
        { q: "The prefix adreno- relates to which gland?", options: ["Pituitary", "Thyroid", "Adrenal", "Pancreas"], answer: "Adrenal" },
        { q: "Diabetes mellitus affects which hormone?", options: ["Insulin", "Estrogen", "Cortisol", "Testosterone"], answer: "Insulin" },
        { q: "What does the suffix -myo or my- refer to?", options: ["Bone", "Muscle", "Nerve", "Blood"], answer: "Muscle" },
        { q: "Muscular dystrophy is a condition where:", options: ["Muscles shrink due to inactivity", "Muscles grow uncontrollably", "Muscles weaken progressively", "Muscles turn into fat"], answer: "Muscles weaken progressively" },
        { q: "Osteoporosis is a condition involving:", options: ["Weakening of bones", "Muscle inflammation", "Joint dislocation", "Bone cancer"], answer: "Weakening of bones" },
        { q: "What does the prefix osteo- mean?", options: ["Joint", "Cartilage", "Bone", "Tendon"], answer: "Bone" }
    ],
    test2: [
        { q: "Choose the correct synonym of 'Abundant'", options: ["Scarce", "Plentiful", "Empty", " Insufficient"], answer: "Plentiful" },
        { q: "Identify the correctly spelled word:", options: [" Recieve", " Receive ", "Receeve", "Recive"], answer: "Receive" },
        { q: "Choose the correct antonym of 'Hostile'", options: ["Friendly", "Rude", "Angry", "Jealous"], answer: "Friendly" },
        { q: "Fill in the blank:She prefers tea __ coffee.", options: ["than", "than to", "over", "instead"], answer: "over" },
        { q: "Choose the correct form:He __ to the market every day.", options: ["go", "gone", "goes", "going"], answer: "goes" },
        { q: " Spot the error:One of the boy have won the prize.", options: ["One", "of the", "boy", "have"], answer: "have" },   
        { q: " Select the correct passive voice:They will complete the project by tomorrow.", options: [" The project will completed by tomorrow.", "The project will be completed by them by tomorrow. ", "The project is completed tomorrow.", " The project is complete tomorrow."], answer: "The project will be completed by them by tomorrow. " },
        { q: "Choose the word most nearly opposite in meaning to 'Expand'", options: [" Enlarge", "Contract ", " Increase ", " Stretch"], answer: "Contract" },
        { q: "Choose the correct sentence:", options: [" He don’t likes mangoes.", "He doesn’t like mangoes.", ". He didn’t liked mangoes.", "He isn’t liking mangoes."], answer: "He doesn’t like mangoes." },
        { q: " Complete the sentence:If I were you, I __ not go there.", options: [" would", " will", " do", " do"], answer: " would" },
        { q: "Pick the correct sentence:", options: ["Its a nice weather.", " It's a nice weather.", " It's nice weather. ", " Its nice weather."], answer: " It's nice weather. " },   
        { q: " Fill in the blank:The news __ very shocking.", options: ["are", "were", "is", "here"], answer: "is" },
        { q: " Choose the correct preposition:He is good __ mathematics.", options: ["in", "at", "on", "for"], answer: "at" },
        { q: "Change to indirect speech:She said, “I am happy.”", options: ["She said she is happy.", " She said she was happy.", "She said that I was happy.", "She said I am happy."], answer: " She said she was happy." },
        { q: "Select the correct synonym of 'Eminent'", options: ["Obscure", " Famous ", " Poor", " Unknown"], answer: " Famous " },
        { q: "Which one is a countable noun?", options: [" Milk", "Sand", "Chair ", " Water"], answer: "Chair " },   
        { q: "Fill in the blank:They __ working since morning.", options: ["is", "are", "have been ", "had"], answer: "have been" },
        { q: " Identify the part of speech:'Quickly'in the sentence: He ran quickly.", options: ["Noun", "Verb", "Adjective", " Adverb "], answer: " Adverb " },
        { q: " Choose the correct tense:By next year, she __ her degree.", options: [" completes", "will have completed", "completed", "has completed"], answer: "will have completed" },
        { q: " Fill in the blank with the correct article:__ honest man always speaks the truth.", options: ["A", "An", "the", "None"], answer: "An" },
        { q: "Choose the correctly punctuated sentence:", options: [" What a lovely day! ", "What a lovely day?", "What a lovely day.", " What a lovely day,"], answer: " What a lovely day! " },   
        { q: "One word substitution:A person who talks too much:", options: [" Introvert", " Extrovert", "Garrulous ", " Silent"], answer: "Garrulous " },
        { q: " Choose the correct question tag:You’re coming to the party, __?", options: [" isn’t it", " are you", "aren’t you", " is it"], answer: "aren’t you" },
        { q: " Choose the correct word:The books are kept on that _.", options: ["shelf", "shelve", "shelfs", " shelvs"], answer: "shelf" },
        { q: " Choose the correct comparative form:This road is _ than the old one.", options: [" wide", " wider ", "widest", "most wide"], answer: " wider " },
    ]
};

let timerInterval;
let timeLeft;

function updateTimerDisplay() {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    document.getElementById("time").textContent = 
        ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')};
}

function startTimer() {
    clearInterval(timerInterval); // Clear any existing timer

    const selectedTest = document.getElementById("test").value;
    let durationInSeconds;

    // Set duration based on the selected test
    if (selectedTest === 'test1') {
        durationInSeconds = 60 * 60; // 60 minutes = 3600 seconds for Anatomy
    } else if (selectedTest === 'test2') {
        durationInSeconds = 25 * 60; // 25 minutes = 1500 seconds for Aptitude
    } else {
        document.getElementById("timerDisplay").style.display = 'none'; // Hide if no test or invalid
        return;
    }

    timeLeft = durationInSeconds;
    document.getElementById("timerDisplay").style.display = 'block';
    updateTimerDisplay();

    timerInterval = setInterval(() => {
        timeLeft--;
        updateTimerDisplay();

        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            alert("Time's up! Your quiz will be submitted automatically.");
            document.getElementById("submitButton").click(); // Simulate form submission
        }
    }, 1000);
}

function loadQuestions() {
    const test = document.getElementById("test").value;
    const container = document.getElementById("questionsContainer");
    container.innerHTML = "";

    // Get a copy of the questions and shuffle them
    const shuffledQuestions = [...quizData[test]]; // Create a shallow copy
    shuffleArray(shuffledQuestions); // Shuffle the copied array

    shuffledQuestions.forEach((q, i) => {
        const div = document.createElement("div");
        div.className = "question";

        // Shuffle options for each question
        const shuffledOptions = [...q.options];
        shuffleArray(shuffledOptions);

        div.innerHTML = <p><strong>${i + 1}. ${q.q}</strong></p> + shuffledOptions.map(opt => `
            <label><input type="radio" name="q${i}" value="${opt.trim()}"> ${opt}</label>
        `).join("");
        container.appendChild(div);
    });
    startTimer(); // Start timer when questions are loaded
}

function calculateScore() {
    const test = document.getElementById("test").value;
    const questions = quizData[test]; // Use the original quizData for scoring

    let score = 0;
    let allAnswered = true;

    const questionElements = document.querySelectorAll('#questionsContainer .question');
    
    if (questionElements.length === 0) { // Handle case where no questions are loaded
        alert("No questions found to score.");
        return false;
    }

    questionElements.forEach((qElement, i) => {
        // Find the question text to match it back to the original quizData
        const questionText = qElement.querySelector('strong').textContent.replace(/^\d+\.\s/, '').trim();
        const originalQuestion = quizData[test].find(q => q.q.trim() === questionText);

        if (!originalQuestion) {
            console.error("Original question not found for scoring:", questionText);
            return; // Skip if original question cannot be found
        }

        const selected = qElement.querySelector(input[name="q${i}"]:checked);
        if (!selected) {
            allAnswered = false;
        } else if (selected.value === originalQuestion.answer.trim()) {
            score++;
        }
    });

    const percentage = ((score / questions.length) * 100).toFixed(2);
    document.getElementById("scoreInput").value = score;
    document.getElementById("percentageInput").value = percentage;
    clearInterval(timerInterval); // Stop the timer on submission
    return true;
}

// Auto-submit quiz when user changes tabs
document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden' && !bothTestsDone) {
        const quizForm = document.getElementById('quizForm');
        const testSelect = document.getElementById('test');
        const questionsContainer = document.getElementById('questionsContainer');

        if (quizForm && testSelect && testSelect.value && questionsContainer.children.length > 0) {
            clearInterval(timerInterval);
            alert("You switched tabs! The quiz will be submitted automatically.");
            if (calculateScore()) {
                quizForm.submit();
                setTimeout(() => {
                    alert("Your quiz has been successfully submitted.");
                }, 100); // Small delay to ensure submission completes
            }
        }
    }
});

function showSection(section) {
    const homeLink = document.getElementById('homeLink');
    const logoutLink = document.getElementById('logoutLink');

    document.getElementById("homeSection").style.display = (section === 'home') ? 'block' : 'none';
    document.getElementById("quizSection").style.display = (section === 'quiz') ? 'block' : 'none';

    // Only disable links if the quiz section is active AND not all tests are completed.
    if (section === 'quiz' && !bothTestsDone) {
        if(homeLink) homeLink.classList.add('disabled-link');
        if(logoutLink) logoutLink.classList.add('disabled-link');
    } else { // This covers the 'home' section OR the quiz section when all tests are done.
        if(homeLink) homeLink.classList.remove('disabled-link');
        if(logoutLink) logoutLink.classList.remove('disabled-link');
        
        // Only clear timer stuff if we are actually going home.
        if (section === 'home') {
            clearInterval(timerInterval); 
            document.getElementById("timerDisplay").style.display = 'none';
        }
    }
}

// Update link state when test selection changes
function updateLinkState() {
    const homeLink = document.getElementById('homeLink');
    const logoutLink = document.getElementById('logoutLink');
    
    if (!bothTestsDone) {
        if(homeLink) homeLink.classList.add('disabled-link');
        if(logoutLink) logoutLink.classList.add('disabled-link');
    } else {
        if(homeLink) homeLink.classList.remove('disabled-link');
        if(logoutLink) logoutLink.classList.remove('disabled-link');
    }
}

window.addEventListener("DOMContentLoaded", () => {
    showSection('home');
    // Initially load questions for the first available test if any
    const testSelect = document.getElementById("test");
    if (testSelect && testSelect.options.length > 0 && !bothTestsDone) {
        loadQuestions(); // This will also start the timer for the initially selected test
    }
});
</script>

<?php if ($redirectToTest2 || $stayOnQuiz): ?>
<script>
    window.addEventListener("DOMContentLoaded", () => {
        showSection('quiz');
        <?php if ($redirectToTest2): ?>
        // The PHP logic now ensures the dropdown only contains 'test2'
        // so we just need to load the questions for the selected option.
        loadQuestions(); 
        <?php endif; ?>
    });
</script>
<?php endif; ?>

<script>
    // --- Security: Disable context menu and specific keys ---
    document.addEventListener('contextmenu', event => event.preventDefault());

    document.addEventListener('keyup', function (e) {
        if (e.key === "PrintScreen") {
            navigator.clipboard.writeText('');
            alert("Screenshots are disabled on this page.");
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.ctrlKey && ['p', 's', 'u', 'c', 'x', 'a'].includes(e.key.toLowerCase())) {
            e.preventDefault();
            alert("This functionality is disabled.");
        }
    });
</script>

</body>
</html>


<script>
document.addEventListener('keydown', function(e) {
  if (
    (e.ctrlKey && e.key === 'u') || 
    (e.ctrlKey && e.shiftKey && e.key === 'I') || 
    (e.ctrlKey && e.shiftKey && e.key === 'J') || 
    (e.ctrlKey && e.key === 's')
  ) {
    e.preventDefault();
    alert("This action is disabled.");
  }
});
</script>