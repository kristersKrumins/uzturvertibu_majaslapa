<?php
require 'Functions/Auth.php';
require 'Database/db.php';
checkAuth();

// 1) Load user from DB
$user_id = $_SESSION['id'];
$sql = "SELECT * FROM users WHERE ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

// Decide photo source
$profile_photo_src = (!empty($user['PROFILE_PHOTO']))
    ? $user['PROFILE_PHOTO']
    : 'bildes/6.jpg';

// If REG_DATE is empty
if (empty($user['REG_DATE']) || $user['REG_DATE'] === '0000-00-00 00:00:00') {
    $now = date('Y-m-d H:i:s');
    $q = "UPDATE users SET REG_DATE = ? WHERE ID = ?";
    $qstmt = $conn->prepare($q);
    $qstmt->bind_param("si", $now, $user_id);
    $qstmt->execute();
    $user['REG_DATE'] = $now;
}

$showBanner = false; // For the success banner

// 2) If user posted => update DB
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic required
    $username = trim($_POST['username'] ?? '');

    // Non-required
    $uzvards = trim($_POST['uzvards'] ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');

    // Possibly empty => only update if not empty
    $new_svars   = trim($_POST['svars']   ?? '');
    $new_augums  = trim($_POST['augums']  ?? '');
    $new_vecums  = trim($_POST['vecums']  ?? '');
    $new_dzimums = trim($_POST['dzimums'] ?? '');
    $new_sports  = trim($_POST['sports']  ?? '');

    // Daily from user input
    $postedKalorijas  = (int)($_POST['kalorijas']      ?? 0);
    $postedProtein    = (int)($_POST['olbaltumvielas'] ?? 0);
    $postedFat        = (int)($_POST['tauki']          ?? 0);
    $postedFatacids   = (int)($_POST['taukskabes']     ?? 0);
    $postedCarb       = (int)($_POST['oglhidrati']     ?? 0);
    $postedSalt       = (int)($_POST['sals']           ?? 0);
    $postedSugar      = (int)($_POST['cukurs']         ?? 0);

    // Handle photo
    $profile_photo_path = (!empty($user['PROFILE_PHOTO']))
        ? $user['PROFILE_PHOTO']
        : 'bildes/6.jpg';

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $tmp  = $_FILES['profile_photo']['tmp_name'];
        $name = $_FILES['profile_photo']['name'];
        $parts= explode('.', $name);
        $ext  = strtolower(end($parts));
        $allowed = ['png','jpg','jpeg','heic','heif','webp','gif'];
        if (in_array($ext, $allowed)) {
            $dir = 'profila_foto/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $newFile = md5(time().$name).'.'.$ext;
            $dest = $dir.$newFile;
            if (move_uploaded_file($tmp, $dest)) {
                // remove old if not default
                if (!empty($user['PROFILE_PHOTO']) &&
                    $user['PROFILE_PHOTO'] !== 'bildes/6.jpg' &&
                    file_exists($user['PROFILE_PHOTO'])) {
                    unlink($user['PROFILE_PHOTO']);
                }
                $profile_photo_path = $dest;
            }
        }
    }

    // If blank => keep old
    $final_svars   = ($new_svars   === '') ? (float)$user['SVARS']   : (float)$new_svars;
    $final_augums  = ($new_augums  === '') ? (float)$user['AUGUMS']  : (float)$new_augums;
    $final_vecums  = ($new_vecums  === '') ? (int)$user['VECUMS']    : (int)$new_vecums;
    $final_dzimums = ($new_dzimums === '') ? $user['DZIMUMS']        : $new_dzimums;
    $final_sports  = ($new_sports  === '') ? $user['SPORTS']         : $new_sports;

    $final_uzvards = ($uzvards === '') ? $user['UZVARDS'] : $uzvards;
    $final_email   = ($email   === '') ? $user['EMAIL']   : $email;
    $final_phone   = ($phone   === '') ? $user['NUMBER']  : $phone;

    // Update DB
    $sql_up = "
        UPDATE users SET
          USERNAME       = ?,
          UZVARDS        = ?,
          EMAIL          = ?,
          NUMBER         = ?,
          SVARS          = ?,
          AUGUMS         = ?,
          VECUMS         = ?,
          KALORIJAS      = ?,
          DZIMUMS        = ?,
          SPORTS         = ?,
          PROFILE_PHOTO  = ?,
          OLBALTUMVIELAS = ?,
          TAUKI          = ?,
          TAUKSKABES     = ?,
          OGLHIDRATI     = ?,
          SALS           = ?,
          CUKURS         = ?
        WHERE ID = ?
    ";
    $stmt = $conn->prepare($sql_up);
    /*
      s, s, s, s, d, d, i, i, s, s, s, i, i, i, i, i, i, i
    */
    $stmt->bind_param(
        "ssssddiisssiiiiiii",
        $username,
        $final_uzvards,
        $final_email,
        $final_phone,
        $final_svars,
        $final_augums,
        $final_vecums,
        $postedKalorijas,
        $final_dzimums,
        $final_sports,
        $profile_photo_path,
        $postedProtein,
        $postedFat,
        $postedFatacids,
        $postedCarb,
        $postedSalt,
        $postedSugar,
        $user_id
    );

    if ($stmt->execute()) {
        // Refresh user
        $sql2 = "SELECT * FROM users WHERE ID = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $user = $stmt2->get_result()->fetch_assoc();

        $profile_photo_src = (!empty($user['PROFILE_PHOTO']))
            ? $user['PROFILE_PHOTO']
            : 'bildes/6.jpg';

        $showBanner = true;
    } else {
        echo "Kļūda, atjauninot profilu: " . $stmt->error;
    }
}

// 3) Prepare "temp" values for the form
// so if user typed something & page reloaded, 
// we show that typed value. Else DB value.
$temp_username = $_POST['username'] ?? $user['USERNAME'];
$temp_uzvards  = $_POST['uzvards']  ?? $user['UZVARDS'];
$temp_phone    = $_POST['phone']    ?? $user['NUMBER'];
$temp_email    = $_POST['email']    ?? $user['EMAIL'];

$temp_svars   = $_POST['svars']   ?? $user['SVARS'];
$temp_augums  = $_POST['augums']  ?? $user['AUGUMS'];
$temp_vecums  = $_POST['vecums']  ?? $user['VECUMS'];
$temp_dzimums = $_POST['dzimums'] ?? $user['DZIMUMS'];
$temp_sports  = $_POST['sports']  ?? $user['SPORTS'];

// daily
$tempKalorijas  = $_POST['kalorijas']      ?? $user['KALORIJAS'];
$tempProtein    = $_POST['olbaltumvielas'] ?? $user['OLBALTUMVIELAS'];
$tempFat        = $_POST['tauki']          ?? $user['TAUKI'];
$tempFatacids   = $_POST['taukskabes']     ?? $user['TAUKSKABES'];
$tempCarb       = $_POST['oglhidrati']     ?? $user['OGLHIDRATI'];
$tempSalt       = $_POST['sals']           ?? $user['SALS'];
$tempSugar      = $_POST['cukurs']         ?? $user['CUKURS'];

// Format registration date
$reg_date_raw = $user['REG_DATE'] ?? '';
$reg_date_fmt = 'Nezināms';
if (!empty($reg_date_raw) && $reg_date_raw !== '0000-00-00 00:00:00') {
    $ts = strtotime($reg_date_raw);
    if ($ts !== false) {
        $reg_date_fmt = date('d-m-Y', $ts);
    }
}

// Old "core" fields for JS
$old_svars   = (float)($user['SVARS']   ?? 0);
$old_augums  = (float)($user['AUGUMS']  ?? 0);
$old_vecums  = (int)  ($user['VECUMS']  ?? 0);
$old_dzimums =        ($user['DZIMUMS'] ?? '');
$old_sports  =        ($user['SPORTS']  ?? '');
?>
<!DOCTYPE html>
<html lang="lv">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profils</title>
  <link 
    href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700&display=swap" 
    rel="stylesheet"
  >
  <link rel="stylesheet" href="CSS/profile.css">
</head>
<body>
<?php if ($showBanner): ?>
<div class="success-banner" id="successBanner">
  Profils atjaunināts veiksmīgi!
</div>
<?php endif; ?>

<div class="profile-container">
  <a href="index.php" class="back-to-home">
    <img src="bildes/7.png" alt="Sākumlapa">
  </a>

  <div class="profile-card">
    <h2>Profils</h2>

    <div class="profile-photo">
      <img 
        src="<?= htmlspecialchars($profile_photo_src) ?>"
        alt="Profila foto" 
        id="profilePhotoImage"
        onclick="document.getElementById('profilePhotoInput').click();"
      >
    </div>

    <div class="profile-info">
      <form 
        method="POST"
        action="profile.php"
        enctype="multipart/form-data"
        id="profileForm"
      >
        <!-- Hidden file input for profile photo -->
        <input 
          type="file"
          id="profilePhotoInput"
          name="profile_photo"
          accept=".png, .jpg, .jpeg, .webp, .heic, .heif, .gif"
          style="display:none"
        >

        <div class="form-columns">
          <!-- LEFT column -->
          <div class="form-column">
            <label for="username">Vārds:</label>
            <input 
              type="text"
              id="username"
              name="username"
              value="<?= htmlspecialchars($temp_username) ?>"
              required
            >

            <label for="uzvards">Uzvārds:</label>
            <input 
              type="text"
              id="uzvards"
              name="uzvards"
              value="<?= htmlspecialchars($temp_uzvards) ?>"
            >

            <label for="phone">Telefona numurs:</label>
            <input 
              type="text"
              id="phone"
              name="phone"
              pattern="[0-9]*"
              title="Tikai cipari"
              value="<?= htmlspecialchars($temp_phone) ?>"
            >

            <label for="email">Epasts:</label>
            <input 
              type="email"
              id="email"
              name="email"
              value="<?= htmlspecialchars($temp_email) ?>"
            >

            <label for="reg_date">Reģistrācijas datums:</label>
            <input
              type="text"
              id="reg_date"
              name="reg_date"
              value="<?= htmlspecialchars($reg_date_fmt) ?>"
              readonly
            >
          </div>

          <!-- MIDDLE column: core fields -->
          <div class="form-column">
            <label for="svars">Svars (kg):</label>
            <input 
              type="number"
              id="svars"
              name="svars"
              step="0.1"
              min="0"
              max="500"
              value="<?= htmlspecialchars($temp_svars) ?>"
            >

            <label for="augums">Augums (cm):</label>
            <input 
              type="number"
              id="augums"
              name="augums"
              step="0.1"
              min="0"
              max="300"
              value="<?= htmlspecialchars($temp_augums) ?>"
            >

            <label for="vecums">Vecums:</label>
            <input 
              type="number"
              id="vecums"
              name="vecums"
              min="0"
              max="150"
              value="<?= htmlspecialchars($temp_vecums) ?>"
            >

            <label for="dzimums">Dzimums:</label>
            <div class="radio-group">
              <label>
                <input 
                  type="radio"
                  name="dzimums"
                  value="vīrietis"
                  <?= ($temp_dzimums === 'vīrietis') ? 'checked' : '' ?>
                >
                Vīrietis
              </label>
              <label>
                <input 
                  type="radio"
                  name="dzimums"
                  value="sieviete"
                  <?= ($temp_dzimums === 'sieviete') ? 'checked' : '' ?>
                >
                Sieviete
              </label>
            </div>

            <label for="sports">Aktivitātes līmenis:</label>
            <select id="sports" name="sports">
              <option value=""
                <?= ($temp_sports === '') ? 'selected' : '' ?>>
                -- Nav izmaiņu --
              </option>
              <option value="nesportoju"
                <?= ($temp_sports === 'nesportoju') ? 'selected' : '' ?>>
                Nesportoju
              </option>
              <option value="knapi sportoju"
                <?= ($temp_sports === 'knapi sportoju') ? 'selected' : '' ?>>
                Dažas reizes mēnesī
              </option>
              <option value="Viegli"
                <?= ($temp_sports === 'Viegli') ? 'selected' : '' ?>>
                1-3 reizes nedēļā
              </option>
              <option value="Mēreni"
                <?= ($temp_sports === 'Mēreni') ? 'selected' : '' ?>>
                4-5 reizes nedēļā
              </option>
              <option value="Aktīvi"
                <?= ($temp_sports === 'Aktīvi') ? 'selected' : '' ?>>
                Katru dienu / intensīvi
              </option>
              <option value="Ļoti aktīvi"
                <?= ($temp_sports === 'Ļoti aktīvi') ? 'selected' : '' ?>>
                Intensīvi 6-7x nedēļā
              </option>
              <option value="Extra aktīvi"
                <?= ($temp_sports === 'Extra aktīvi') ? 'selected' : '' ?>>
                Ļoti intensīvi / smags darbs
              </option>
            </select>
          </div>

          <!-- RIGHT column (daily) -->
          <div class="form-column recommended-column">
            <h3>Dienas Normas</h3>

            <label for="kalorijas">Kalorijas (kcal):</label>
            <input
              type="number"
              id="kalorijas"
              name="kalorijas"
              value="<?= htmlspecialchars($tempKalorijas) ?>"
              min="0"
              max="15000"
            >

            <label for="olbaltumvielas">Olbaltumvielas (g):</label>
            <input
              type="number"
              id="olbaltumvielas"
              name="olbaltumvielas"
              value="<?= htmlspecialchars($tempProtein) ?>"
              min="0"
              max="600"
            >

            <label for="tauki">Tauki (g):</label>
            <input
              type="number"
              id="tauki"
              name="tauki"
              value="<?= htmlspecialchars($tempFat) ?>"
              min="0"
              max="400"
            >

            <label for="taukskabes">Taukskābes (g):</label>
            <input
              type="number"
              id="taukskabes"
              name="taukskabes"
              value="<?= htmlspecialchars($tempFatacids) ?>"
              min="0"
              max="150"
            >

            <label for="oglhidrati">Ogļhidrāti (g):</label>
            <input
              type="number"
              id="oglhidrati"
              name="oglhidrati"
              value="<?= htmlspecialchars($tempCarb) ?>"
              min="0"
              max="1000"
            >

            <label for="sals">Sāls (g):</label>
            <input
              type="number"
              id="sals"
              name="sals"
              value="<?= htmlspecialchars($tempSalt) ?>"
              min="0"
              max="20"
            >

            <label for="cukurs">Cukurs (g):</label>
            <input
              type="number"
              id="cukurs"
              name="cukurs"
              value="<?= htmlspecialchars($tempSugar) ?>"
              min="0"
              max="300"
            >
          </div>
        </div>
        <button type="submit" class="save-button">Saglabāt</button>
      </form>
    </div>
  </div>
</div>

<!-- Popup for recommended daily if changed -->
<div id="popupOverlay">
  <div id="popupModal">
    <h2>Dienas Normu Ieteikumi</h2>
    <p>Vai vēlies nomainīt dienas normas ar ieteicamām vērtībām?</p>
    <div class="popup-buttons">
      <button id="yesButton">Jā</button>
      <button id="noButton">Nē</button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const banner = document.getElementById('successBanner');
  if (banner) {
    setTimeout(() => {
      banner.style.display = 'none';
    }, 3000);
  }
});

// Preview chosen image
document.getElementById('profilePhotoInput').addEventListener('change', function(e){
  const [file] = e.target.files;
  if (file) {
    document.getElementById('profilePhotoImage').src = URL.createObjectURL(file);
  }
});

// Old DB values for detection
let oldSvars   = <?= json_encode((float)$old_svars) ?>;
let oldAugums  = <?= json_encode((float)$old_augums) ?>;
let oldVecums  = <?= json_encode((int)$old_vecums)   ?>;
let oldDzimums = <?= json_encode($old_dzimums)       ?>;
let oldSports  = <?= json_encode($old_sports)        ?>;

// Form
let form       = document.getElementById('profileForm');
let svarsF     = document.getElementById('svars');
let augumsF    = document.getElementById('augums');
let vecumsF    = document.getElementById('vecums');
let dzimumsRadios = document.getElementsByName('dzimums');
let sportsSel  = document.getElementById('sports');

// Daily fields
let kalF   = document.getElementById('kalorijas');
let protF  = document.getElementById('olbaltumvielas');
let fatF   = document.getElementById('tauki');
let fatAF  = document.getElementById('taukskabes');
let carbF  = document.getElementById('oglhidrati');
let saltF  = document.getElementById('sals');
let sugarF = document.getElementById('cukurs');

// Popup
let overlay = document.getElementById('popupOverlay');
let modal   = document.getElementById('popupModal');
let yesBtn  = document.getElementById('yesButton');
let noBtn   = document.getElementById('noButton');
overlay.style.display = 'none';

// Show popup only once
let hasSeenPopup = false;

form.addEventListener('submit', function(e){
  if (hasSeenPopup) {
    // Let it submit
    return;
  }

  // Check if core changed
  let newSvars   = (svarsF.value.trim()   === '') ? oldSvars   : parseFloat(svarsF.value);
  let newAugums  = (augumsF.value.trim()  === '') ? oldAugums  : parseFloat(augumsF.value);
  let newVecums  = (vecumsF.value.trim()  === '') ? oldVecums  : parseInt(vecumsF.value);

  let newDzimums = oldDzimums;
  dzimumsRadios.forEach(r => { if (r.checked) newDzimums = r.value; });
  if (!newDzimums) newDzimums = oldDzimums;

  let newSports = sportsSel.value.trim() || oldSports;

  let changed = false;
  if (newSvars   !== oldSvars)   changed = true;
  if (newAugums  !== oldAugums)  changed = true;
  if (newVecums  !== oldVecums)  changed = true;
  if (newDzimums !== oldDzimums) changed = true;
  if (newSports  !== oldSports)  changed = true;

  if (changed) {
    e.preventDefault();
    overlay.style.display = 'flex';

    yesBtn.onclick = function() {
      // immediate BMR calc
      let bmr = 0;
      if (newDzimums === 'vīrietis') {
        bmr = 66 + (13.7 * newSvars) + (5 * newAugums) - (6.8 * newVecums);
      } else if (newDzimums === 'sieviete') {
        bmr = 655 + (9.6 * newSvars) + (1.8 * newAugums) - (4.7 * newVecums);
      }
      let af = 1.0;
      switch(newSports) {
        case 'nesportoju':       af = 1.0; break;
        case 'knapi sportoju':   af = 1.2; break;
        case 'Viegli':           af = 1.375; break;
        case 'Mēreni':           af = 1.55; break;
        case 'Aktīvi':           af = 1.725; break;
        case 'Ļoti aktīvi':      af = 1.8; break;
        case 'Extra aktīvi':     af = 1.95; break;
      }

      let recKcal   = Math.round(bmr * af);
      let recProt   = Math.round(newSvars * 1.6);
      let recFat    = Math.round((recKcal*0.25)/9);
      let recFatA   = Math.round((recKcal*0.1)/9);
      let recCarb   = Math.round((recKcal*0.55)/4);
      let recSalt   = 5;
      let recSugar  = Math.round((recKcal*0.1)/4);

      // fill daily fields
      kalF.value   = recKcal;
      protF.value  = recProt;
      fatF.value   = recFat;
      fatAF.value  = recFatA;
      carbF.value  = recCarb;
      saltF.value  = recSalt;
      sugarF.value = recSugar;

      overlay.style.display = 'none';
      hasSeenPopup = true;
    };

    noBtn.onclick = function() {
      overlay.style.display = 'none';
      hasSeenPopup = true;
    };
  }
});
</script>



</body>
</html>
