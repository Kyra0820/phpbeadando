<?php
session_start();

// Példa bejelentkezés (ha szükséges a teszteléshez):
// $_SESSION['loggedin'] = true;
// $_SESSION['username'] = 'Valaki';

// Üdvözlő üzenet
$welcomeMessage = "Üdvözöllek!";
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['username'])) {
    $welcomeMessage = "Üdvözöllek, " . htmlspecialchars($_SESSION['username']) . "!";
}

// Kijelentkezés
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: web.php");
    exit;
}

// Rangsor fájl:
$scoreboardFile = 'scoreboard.txt';

// Ha scoreboard frissítő paraméter érkezik (?score=win vagy ?score=loss),
if (isset($_GET['score']) && isset($_SESSION['username'])) {
    $scoreType = $_GET['score']; // 'win' vagy 'loss'
    updateScoreboard($_SESSION['username'], $scoreType, $scoreboardFile);

    // F5 duplázás elkerülése: paraméterek nélkül frissítjük az oldalt
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/**
 *  Megjeleníti a scoreboardot, 
 *  - Csökkenő sorrendben a győzelmek száma szerint
 *  - Sorszámozva (1., 2., 3., …)
 *  - Zöld stílusú, lekerekített táblázat
 */
function displayScoreboard($scoreboardFile) {
    if (!file_exists($scoreboardFile)) {
        return "<p>Nincs még ranglista...</p>";
    }
    $lines = file($scoreboardFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return "<p>Nincs még ranglista...</p>";
    }

    // Betöltjük egy asszociatív tömbbe
    $data = [];  // pl. [ 'Valaki' => ['wins'=>3,'losses'=>2], ... ]

    foreach ($lines as $line) {
        list($user, $wins, $losses) = explode("|", $line);
        $user = trim($user);
        $wins = (int)trim($wins);
        $losses = (int)trim($losses);

        $data[$user] = [
            'wins' => $wins,
            'losses' => $losses
        ];
    }

    // Rendezés csökkenő sorrendben a 'wins' alapján
    uasort($data, function($a, $b) {
        // Ha A-nak több a wins, akkor előre kerül
        return $b['wins'] <=> $a['wins'];
    });

    // Stílus
    $output = "<h2 style='text-align:center;'>Rangsor</h2>";
    $output .= "<table style='
        margin: 0 auto;
        border-collapse: collapse;
        border: 2px solid #4CAF50;
        border-radius: 10px;
        overflow: hidden;
        background-color: #006400;
        color: white;
    '>";
    // Fejléc
    $output .= "
    <tr style='border-bottom: 2px solid #4CAF50;'>
        <th style='padding:8px; border-right:2px solid #4CAF50;'>#</th>
        <th style='padding:8px; border-right:2px solid #4CAF50;'>Felhasználónév</th>
        <th style='padding:8px; border-right:2px solid #4CAF50;'>Győzelmek</th>
        <th style='padding:8px;'>Vesztések</th>
    </tr>";

    // Sorszámozás
    $rank = 1;
    foreach ($data as $username => $info) {
        $wins = $info['wins'];
        $losses = $info['losses'];

        $output .= "<tr style='border-bottom:1px solid #4CAF50;'>
            <td style='padding:8px; text-align:center; border-right:1px solid #4CAF50;'>{$rank}.</td>
            <td style='padding:8px; text-align:center; border-right:1px solid #4CAF50;'>{$username}</td>
            <td style='padding:8px; text-align:center; border-right:1px solid #4CAF50;'>{$wins}</td>
            <td style='padding:8px; text-align:center;'>{$losses}</td>
        </tr>";
        $rank++;
    }

    $output .= "</table>";
    return $output;
}

/**
 * Rangsor frissítése scoreboard.txt-ben
 */
function updateScoreboard($username, $result, $file) {
    // Beolvasás
    $data = [];
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            list($user, $wins, $losses) = explode("|", $line);
            $user = trim($user);
            $wins = (int)trim($wins);
            $losses = (int)trim($losses);
            $data[$user] = [
                'wins'   => $wins,
                'losses' => $losses
            ];
        }
    }

    if (!isset($data[$username])) {
        $data[$username] = ['wins' => 0, 'losses' => 0];
    }

    // Eredmény szerint
    if ($result === 'win') {
        $data[$username]['wins']++;
    } elseif ($result === 'loss') {
        $data[$username]['losses']++;
    }

    // Mentés scoreboard.txt-be
    $fh = fopen($file, 'w');
    foreach ($data as $user => $scores) {
        fwrite($fh, "{$user} | {$scores['wins']} | {$scores['losses']}\n");
    }
    fclose($fh);
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blackjack Játék</title>
    <!-- SweetAlert2 CSS + JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background-color: #006400; 
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        #blackjackCanvas {
            background-color: green;
            border: 2px solid black;
            display: block;
            margin: auto;
        }
        button {
            margin: 5px;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            color: white;
            background-color: #4CAF50;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        button:active {
            background-color: #3e8e41;
        }
        #welcome {
            text-align: center;
            margin-top: 20px;
            font-size: 24px;
        }
        h1 {
            text-align: center;
        }
        #controls {
            margin: 10px;
            font-size: 18px;
            text-align: center;
        }
        #chipsDisplay {
            position: fixed;
            right: 20px;
            top: 100px;
            background-color: #4CAF50;
            padding: 10px;
            font-size: 18px;
            color: white;
            text-align: center;
        }
        #logoutForm {
            text-align: center;
            margin: 30px 0;
        }
        #logoutButton {
            background-color: red !important;
            margin: 0 auto;
            display: block;
        }
        #logoutButton:hover {
            background-color: darkred !important;
        }
        /* Ranglista a zseton kijelző alatt */
        #scoreboard {
            margin: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Üdvözlő üzenet -->
    <div id="welcome"><?php echo $welcomeMessage; ?></div>

    <h1>Blackjack Játék</h1>
    <canvas id="blackjackCanvas" width="800" height="400"></canvas>

    <!-- Gombok -->
    <div id="controls">
        <button onclick="placeBet(100)">Tét Elhelyezése (100 Zseton)</button>
        <button onclick="startGame()">Új Játék</button>
        <button onclick="hit()">Hit</button>
        <button onclick="stand()">Stand</button>
        <button onclick="doubleDown()">Duplázás</button>
    </div>

    <!-- Zseton kijelző -->
    <div id="chipsDisplay">Aktuális Zsetonok: 1000</div>

    <!-- Ranglista (rendezve, sorszámozva, zöld stílusban) -->
    <div id="scoreboard">
        <?php echo displayScoreboard($scoreboardFile); ?>
    </div>

    <!-- Kijelentkezés -->
    <div id="logoutForm">
        <form action="" method="get">
            <button type="submit" name="logout" value="1" id="logoutButton">Kijelentkezés</button>
        </form>
    </div>

    <!-- Blackjack logika (rövidítve) -->
    <script>
        let playerHand = [];
        let dealerHand = [];
        let deck = [];
        let playerChips = 1000;
        let currentBet = 0;
        const maxChips = 1300;

        const canvas = document.getElementById('blackjackCanvas');
        const ctx = canvas.getContext('2d');

        function startGame() {
            if (currentBet === 0) {
                Swal.fire({
                    title: 'Figyelmeztetés',
                    text: 'Először tegyél egy tétet!',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }
            deck = createMultiDeck(3);
            shuffleDeck(deck);
            playerHand = [drawCard(), drawCard()];
            dealerHand = [drawCard(), drawCard()];
            drawTable();
            checkBlackjack();
        }

        function checkBlackjack() {
            const pScore = calculateScore(playerHand);
            const dScore = calculateScore(dealerHand);

            if (pScore === 21) {
                playerChips += currentBet * 2;
                currentBet = 0;
                updateChipsDisplay();

                Swal.fire({
                    title: 'Blackjack!',
                    text: 'Gratulálok, nyertél!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (playerChips >= maxChips) {
                        location.href = "?score=win";
                    }
                });
            }
            else if (dScore === 21) {
                currentBet = 0;
                updateChipsDisplay();

                Swal.fire({
                    title: 'Vesztettél!',
                    text: 'Az osztónak blackjackje van.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (playerChips <= 0) {
                        location.href = "?score=loss";
                    }
                });
            }
        }

        function hit() {
            playerHand.push(drawCard());
            drawTable();
            const pScore = calculateScore(playerHand);
            if (pScore > 21) {
                currentBet = 0;
                updateChipsDisplay();
                Swal.fire({
                    title: 'Besokalltál!',
                    text: 'Vesztettél!',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (playerChips <= 0) {
                        location.href = "?score=loss";
                    }
                });
            }
        }

        function stand() {
            while (calculateScore(dealerHand) < 17) {
                dealerHand.push(drawCard());
            }
            drawTable();

            const pScore = calculateScore(playerHand);
            const dScore = calculateScore(dealerHand);

            if (dScore > 21) {
                playerChips += currentBet * 2;
                currentBet = 0;
                updateChipsDisplay();
                Swal.fire({
                    title: 'Nyertél!',
                    text: 'Az osztó besokallt!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (playerChips >= maxChips) {
                        location.href = "?score=win";
                    }
                });
            }
            else if (pScore > dScore) {
                playerChips += currentBet * 2;
                currentBet = 0;
                updateChipsDisplay();
                Swal.fire({
                    title: 'Nyertél!',
                    text: 'Gratulálok!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (playerChips >= maxChips) {
                        location.href = "?score=win";
                    }
                });
            }
            else if (pScore < dScore) {
                currentBet = 0;
                updateChipsDisplay();
                Swal.fire({
                    title: 'Vesztettél!',
                    text: 'Az osztó nyert.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (playerChips <= 0) {
                        location.href = "?score=loss";
                    }
                });
            }
            else {
                // Döntetlen
                playerChips += currentBet;
                currentBet = 0;
                updateChipsDisplay();
                Swal.fire({
                    title: 'Döntetlen!',
                    text: 'Nincs győztes.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            }
        }

        function doubleDown() {
            if (playerChips < currentBet) {
                Swal.fire({
                    title: 'Hiba',
                    text: 'Nincs elég zsetonod a duplázáshoz!',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }
            playerChips -= currentBet;
            currentBet *= 2;
            updateChipsDisplay();

            playerHand.push(drawCard());
            drawTable();
            // automatikusan stand
            stand();
        }

        function placeBet(amount) {
            if (amount > playerChips) {
                Swal.fire({
                    title: 'Hiba',
                    text: 'Nincs elég zsetonod!',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }
            currentBet = amount;
            playerChips -= amount;
            updateChipsDisplay();
        }

        function updateChipsDisplay() {
            document.getElementById("chipsDisplay").innerText =
                `Aktuális Zsetonok: ${playerChips}`;
        }

        function calculateScore(hand) {
            let score = 0;
            let aceCount = 0;
            hand.forEach(card => {
                score += card.value;
                if (card.name === 'A') aceCount++;
            });
            while (score <= 11 && aceCount > 0) {
                score += 10;
                aceCount--;
            }
            return score;
        }

        function createDeck() {
            const suits = ['♥','♦','♣','♠'];
            const values = [
                { name: 'A', value: 1 },
                { name: '2', value: 2 },
                { name: '3', value: 3 },
                { name: '4', value: 4 },
                { name: '5', value: 5 },
                { name: '6', value: 6 },
                { name: '7', value: 7 },
                { name: '8', value: 8 },
                { name: '9', value: 9 },
                { name: '10', value: 10 },
                { name: 'J', value: 10 },
                { name: 'Q', value: 10 },
                { name: 'K', value: 10 }
            ];
            const deck = [];
            for (const suit of suits) {
                for (const val of values) {
                    deck.push({ suit, ...val });
                }
            }
            return deck;
        }

        function createMultiDeck(numDecks = 3) {
            let multiDeck = [];
            for (let i = 0; i < numDecks; i++) {
                multiDeck = multiDeck.concat(createDeck());
            }
            return multiDeck;
        }

        function shuffleDeck(deck) {
            for (let i = deck.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [deck[i], deck[j]] = [deck[j], deck[i]];
            }
        }

        function drawCard() {
            return deck.pop();
        }

        function drawTable() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            drawHand(playerHand, 100, 300, "Játékos Keze:", false);
            drawHand(dealerHand, 100, 50, "Osztó Keze:", dealerHand.length > 1);
        }

        function drawHand(hand, x, y, label, hideFirstCard) {
            ctx.font = '20px Arial';
            ctx.fillStyle = 'white';
            ctx.fillText(label, x, y - 20);

            hand.forEach((card, i) => {
                if (i === 0 && hideFirstCard) {
                    drawCardBack(x + i*60, y);
                } else {
                    drawCardFace(card, x + i*60, y);
                }
            });
        }

        function drawCardFace(card, x, y) {
            ctx.fillStyle = 'white';
            ctx.fillRect(x, y, 50, 70);
            ctx.strokeStyle = 'black';
            ctx.strokeRect(x, y, 50, 70);

            ctx.fillStyle = (card.suit === '♥'||card.suit === '♦') ? 'red' : 'black';
            ctx.font = '20px Arial';
            ctx.fillText(card.suit + card.name, x+10, y+40);
        }

        function drawCardBack(x, y) {
            ctx.fillStyle = 'blue';
            ctx.fillRect(x, y, 50, 70);
            ctx.strokeStyle = 'black';
            ctx.strokeRect(x, y, 50, 70);
            ctx.fillStyle = 'white';
            ctx.fillText("?", x+20, y+40);
        }
    </script>
</body>
</html>
