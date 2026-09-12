<?php
// admin/upload_poster.php
session_start(); 
include '../includes/db_connect.php'; 

if (isset($conn)) {
    $conn->set_charset("utf8mb4");
}

// 🌟 AJAX Request Handling: अगर सर्च रिक्वेस्ट AJAX से आ रही है तो सिर्फ टेबल बॉडी रिटर्न करें
if (isset($_GET['ajax_search'])) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    if (!empty($search)) {
        $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM posters WHERE title LIKE ?");
        $search_param = "%" . $search . "%";
        $count_stmt->bind_param("s", $search_param);
        $count_stmt->execute();
        $count_res = $count_stmt->get_result()->fetch_assoc();
        $total_rows = $count_res['total'];
        $count_stmt->close();

        $data_stmt = $conn->prepare("SELECT * FROM posters WHERE title LIKE ? ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
        $data_stmt->bind_param("sii", $search_param, $limit, $offset);
    } else {
        $count_res = $conn->query("SELECT COUNT(*) as total FROM posters")->fetch_assoc();
        $total_rows = $count_res['total'];

        $data_stmt = $conn->prepare("SELECT * FROM posters ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
        $data_stmt->bind_param("ii", $limit, $offset);
    }

    $data_stmt->execute();
    $result = $data_stmt->get_result();
    $total_pages = ceil($total_rows / $limit);

    // HTML Output Generation for AJAX
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo '<tr>
                <td><img src="../banners/'.htmlspecialchars($row['image_name']).'" class="portal-thumb"></td>
                <td>
                    <div class="fw-bold text-dark custom-truncate" title="'.htmlspecialchars($row['title']).'">'.htmlspecialchars($row['title']).'</div>
                    <small class="text-muted custom-truncate" title="'.htmlspecialchars($row['description']).'">'.htmlspecialchars($row['description']).'</small>
                </td>
                <td><span class="badge-date">'.date('d M, Y', strtotime($row['uploaded_at'])).'</span></td>
                <td class="text-end">
                    <div class="btn-group gap-1">
                        <a href="upload_poster.php?edit_id='.$row['id'].'&page='.$page.'&search='.urlencode($search).'" class="btn btn-sm btn-outline-primary px-2" style="border-radius: 6px;"><i class="fas fa-edit"></i></a>
                        <a href="upload_poster.php?delete_id='.$row['id'].'" class="btn btn-sm btn-outline-danger px-2" style="border-radius: 6px;" onclick="return confirm(\'क्या आप वाकई इसे हटाना चाहते हैं?\')"><i class="fas fa-trash-alt"></i></a>
                    </div>
                </td>
            </tr>';
        }
    } else {
        echo '<tr><td colspan="4" class="text-center py-5 text-muted"><i class="far fa-images d-block display-5 mb-2 text-warning"></i>कोई भी पोस्टर नहीं मिला।</td></tr>';
    }
    $data_stmt->close();
    exit; // AJAX रिस्पॉन्स के बाद आगे का HTML लोड न हो
}

$msg = "";
if (isset($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']); 
}

// Helper Function: Year/Month Sub-folders
function getTargetUploadDir() {
    $base_dir = "../banners/";
    $sub_dir = date('Y') . '/' . date('m') . '/';
    $full_path = $base_dir . $sub_dir;
    if (!file_exists($full_path)) {
        mkdir($full_path, 0755, true);
    }
    return $sub_dir;
}

// ==========================================
// 1. डिलीट लॉजिक
// ==========================================
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    $stmt = $conn->prepare("SELECT image_name FROM posters WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $file_path = "../banners/" . $row['image_name'];
        if (!empty($row['image_name']) && file_exists($file_path)) {
            unlink($file_path); 
        }
    }
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM posters WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['flash_msg'] = "<div class='alert alert-success animate-pop'><i class='fas fa-trash-alt me-2'></i> पोस्टर सफलतापूर्वक डिलीट कर दिया गया!</div>";
    } else {
        $_SESSION['flash_msg'] = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i> डिलीट करने में त्रुटि आई।</div>";
    }
    $stmt->close();
    
    header("Location: upload_poster.php");
    exit();
}

// ==========================================
// 2. एडिट लॉजिक - डेटा लोड करना
// ==========================================
$edit_mode = false;
$edit_id = 0;
$edit_title = "";
$edit_desc = "";
$edit_image = "";

if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_id = (int)$_GET['edit_id'];
    
    $stmt = $conn->prepare("SELECT * FROM posters WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $edit_title = $row['title'];
        $edit_desc = $row['description'];
        $edit_image = $row['image_name'];
    }
    $stmt->close();
}

// ==========================================
// 3. सबमिट लॉजिक (INSERT या UPDATE)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $p_id = isset($_POST['poster_id']) ? (int)$_POST['poster_id'] : 0;
    $err_triggered = false;

    if (isset($_FILES["poster_image"]) && $_FILES["poster_image"]["error"] == 0) {
        $sub_path = getTargetUploadDir();
        $target_dir = "../banners/" . $sub_path;
        
        $image_name = $sub_path . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($_FILES["poster_image"]["name"], PATHINFO_EXTENSION);
        $target_file = "../banners/" . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, ['jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($_FILES["poster_image"]["tmp_name"], $target_file)) {
                if ($p_id > 0) {
                    $stmt = $conn->prepare("SELECT image_name FROM posters WHERE id = ?");
                    $stmt->bind_param("i", $p_id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $old_file = "../banners/" . $row['image_name'];
                        if (!empty($row['image_name']) && file_exists($old_file)) unlink($old_file);
                    }
                    $stmt->close();
                }
            } else {
                $image_name = "";
                $_SESSION['flash_msg'] = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle me-2'></i> इमेज सेव करने में दिक्कत आई।</div>";
                $err_triggered = true;
            }
        } else {
            $image_name = "";
            $_SESSION['flash_msg'] = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle me-2'></i> सिर्फ JPG, JPEG, PNG अनुमति है।</div>";
            $err_triggered = true;
        }
    } else {
        $image_name = isset($_POST['old_image']) ? $_POST['old_image'] : "";
    }

    if (!$err_triggered) {
        if ($p_id > 0) {
            $stmt = $conn->prepare("UPDATE posters SET title = ?, description = ?, image_name = ? WHERE id = ?");
            $stmt->bind_param("sssi", $title, $description, $image_name, $p_id);
            if ($stmt->execute()) {
                $_SESSION['flash_msg'] = "<div class='alert alert-success animate-pop'><i class='fas fa-check-circle me-2'></i> पोस्टर सफलतापूर्वक अपडेट हो गया!</div>";
            }
            $stmt->close();
        } else {
            if (!empty($image_name)) {
                $stmt = $conn->prepare("INSERT INTO posters (title, description, image_name) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $title, $description, $image_name);
                if ($stmt->execute()) {
                    $_SESSION['flash_msg'] = "<div class='alert alert-success animate-pop'><i class='fas fa-paper-plane me-2'></i> नया पोस्टर सफलतापूर्वक लाइव न्यूज़ फीड में जुड़ गया!</div>";
                }
                $stmt->close();
            } else {
                $_SESSION['flash_msg'] = "<div class='alert alert-danger'><i class='fas fa-image me-2'></i> कृपया एक पोस्टर इमेज जरूर चुनें।</div>";
            }
        }
    }

    header("Location: upload_poster.php");
    exit();
}

// Normal Page Load Main Query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$count_res = $conn->query("SELECT COUNT(*) as total FROM posters")->fetch_assoc();
$total_rows = $count_res['total'];

$data_stmt = $conn->prepare("SELECT * FROM posters ORDER BY uploaded_at DESC LIMIT ? OFFSET ?");
$data_stmt->bind_param("ii", $limit, $offset);
$data_stmt->execute();
$result = $data_stmt->get_result();
$total_pages = ceil($total_rows / $limit);
?>

<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>एडमिन Poster न्यूज़ रूम</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind:wght@400;500;600;700&display=swap">
    
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --header-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            --card-bg: rgba(255, 255, 255, 1);
            --text-dark: #1e293b;
            --text-light: #64748b;
            --accent-purple: #4f46e5;
        }
        
        body { 
            font-family: 'Hind', sans-serif; 
            background: var(--bg-gradient);
            color: var(--text-dark);
            margin: 0; padding: 0;
        }

        .app-container { display: flex; width: 100%; min-height: 100vh; }
        .main-content { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .portal-header { background: var(--header-gradient); color: white; padding: 20px 25px; border-bottom: 4px solid #6366f1; }
        .portal-logo { font-weight: 700; font-size: 1.35rem; display: flex; align-items: center; }
        .portal-logo span { background: #fff; color: #0f172a; padding: 3px 12px; border-radius: 50px; margin-left: 10px; font-size: 0.9rem; font-weight: 700; }
        .content-body { padding: 25px; }
        .news-card { background: var(--card-bg); border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; }
        .card-header-custom { background: #f1f5f9; padding: 16px 20px; font-weight: 700; font-size: 1.05rem; color: var(--text-dark); display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #e2e8f0; }
        
        .form-label { font-weight: 700; color: #475569; font-size: 0.9rem; margin-bottom: 6px; }
        .form-control { background-color: #ffffff; color: #334155; border-radius: 8px; padding: 10px 14px; border: 1px solid #cbd5e1; font-size: 0.9rem; font-weight: 500; }
        .form-control:focus { border-color: var(--accent-purple); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15); outline: none; }

        .image-upload-wrapper { border: 2px dashed #cbd5e1; border-radius: 10px; padding: 25px 15px; text-align: center; background: #f8fafc; cursor: pointer; position: relative; }
        .image-upload-wrapper i { color: var(--accent-purple); font-size: 2rem; }
        .image-upload-wrapper input[type="file"] { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        #imagePreview { max-height: 150px; border-radius: 8px; display: none; margin-top: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 2px solid #fff; }

        .btn-portal { background: #4f46e5; color: white; font-weight: 600; border: none; padding: 12px 20px; border-radius: 8px; transition: all 0.2s ease; }
        .btn-portal:hover { background: #4338ca; color: white; }
        .btn-update { background: #0284c7; }
        .btn-update:hover { background: #0369a1; }

        /* 🌟 फिक्स्ड टेबल और बॉक्स लेआउट सीएसएस */
        .table-responsive { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .table-custom { margin-bottom: 0; background-color: #fff; width: 100%; table-layout: fixed; }
        .table-custom th { background: #f1f5f9; color: #475569; padding: 14px; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .table-custom td { padding: 14px; vertical-align: middle; background: #fff; border-bottom: 1px solid #edf2f7; font-size: 0.9rem; overflow: hidden; }
        
        .portal-thumb { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
        .badge-date { background: #f1f5f9; color: #475569; font-weight: 600; padding: 4px 10px; border-radius: 30px; font-size: 0.8rem; display: inline-block; white-space: nowrap; }
        .custom-truncate { display: block; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .alert { border-radius: 8px; border: none; font-weight: 600; font-size: 0.9rem; }
    </style>
</head>
<body>

    <div class="app-container">
        <?php include 'includes/admin_header.php'; ?>

        <div class="main-content">
            <header class="portal-header">
                <div class="container-fluid p-0 d-flex justify-content-between align-items-center">
                    <div class="portal-logo">
                        <i class="fas fa-bolt text-warning me-2"></i>TC DIGITAL<span>LIVE Poster ROOM</span>
                    </div>
                    <div>
                        <a href="../poster.php" target="_blank" class="btn btn-light btn-sm px-4 rounded-pill fw-bold text-primary shadow-sm"><i class="fas fa-external-link-alt me-2"></i>लाइव पोर्टल देखें</a>
                    </div>
                </div>
            </header>

            <div class="content-body">
                <div class="container-fluid p-0">
                    
                    <!-- 🌟 लाइव सर्च इनपुट बॉक्स -->
                    <div class="row mb-4">
                        <div class="col-md-6 offset-md-6">
                            <div class="position-relative">
                                <input type="text" id="ajaxSearchInput" class="form-control autocomplete" placeholder="2-4 अक्षर टाइप करके तुरंत खोजें..." value="<?= htmlspecialchars($search) ?>" oninput="doLiveSearch(this.value)">
                                <span class="position-absolute end-0 top-50 translate-middle-y me-3 text-secondary" id="searchSpinner" style="display:none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- फ़ॉर्म सेक्शन -->
                        <div class="col-lg-5">
                            <div class="news-card sticky-top" style="top: 20px; z-index: 10;">
                                <div class="card-header-custom">
                                    <i class="fas <?php echo $edit_mode ? 'fa-magic text-primary' : 'fa-rocket text-danger'; ?>"></i>
                                    <?php echo $edit_mode ? 'पोस्टर विवरण बदलें' : 'नया पोस्टर / सूचना लाइव करें'; ?>
                                </div>
                                <div class="card-body p-4">
                                    <?php echo $msg; ?>
                                    <form action="upload_poster.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="poster_id" value="<?php echo $edit_id; ?>">
                                        <input type="hidden" name="old_image" value="<?php echo $edit_image; ?>">

                                        <div class="mb-3">
                                            <label class="form-label">मुख्य हेडिंग (Title)</label>
                                            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($edit_title); ?>" placeholder="उदा. नया बैच प्रारंभ" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">पूरा विवरण (Description)</label>
                                            <textarea class="form-control" name="description" rows="4" placeholder="यहाँ विवरण लिखें..." required><?php echo htmlspecialchars($edit_desc); ?></textarea>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label class="form-label">आकर्षक पोस्टर इमेज चुनें</label>
                                            <div class="image-upload-wrapper">
                                                <i class="fas fa-images mb-2"></i>
                                                <p class="mb-0 fw-bold text-dark small">गैलरी से फाइल सेलेक्ट करें</p>
                                                <input type="file" name="poster_image" id="fileInput" <?php echo $edit_mode ? '' : 'required'; ?>>
                                            </div>
                                            
                                            <div class="text-center">
                                                <img id="imagePreview" src="#" alt="Preview">
                                                <?php if($edit_mode): ?>
                                                    <div id="oldImgDiv" class="mt-3">
                                                        <small class="text-secondary d-block mb-2 fw-bold">वर्तमान चालू पोस्टर:</small>
                                                        <img src="../banners/<?php echo $edit_image; ?>" class="rounded shadow-sm" style="max-height: 100px; border: 2px solid #4f46e5;">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2">
                                            <?php if($edit_mode): ?>
                                                <button type="submit" name="submit" class="btn btn-portal btn-update"><i class="fas fa-check-circle me-2"></i> बदलाव सेव करें</button>
                                                <a href="upload_poster.php" class="btn btn-light py-2 border fw-bold text-secondary" style="border-radius:8px;"><i class="fas fa-times me-2"></i> कैंसिल करें</a>
                                            <?php else: ?>
                                                <button type="submit" name="submit" class="btn btn-portal"><i class="fas fa-paper-plane me-2"></i> तुरंत पब्लिश करें</button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- लाइव फीड्स लिस्ट -->
                        <div class="col-lg-7">
                            <div class="news-card">
                                <div class="card-header-custom justify-content-between">
                                    <span class="text-dark fw-bold"><i class="fas fa-photo-video text-primary me-2"></i> एक्टिव पोस्टर्स (Total: <?= $total_rows ?>)</span>
                                    <span class="badge bg-success rounded-pill px-3 py-2 fw-bold" style="font-size: 0.75rem;">ACTIVE FEED</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-custom">
                                            <thead>
                                                <tr>
                                                    <!-- 🌟 सभी बॉक्स की चौड़ाई फिक्स कर दी गई है -->
                                                    <th style="width: 85px;">पोस्टर</th>
                                                    <th>हेडलाइन व जानकारी</th>
                                                    <th style="width: 125px;">दिनांक</th>
                                                    <th style="width: 100px;" class="text-end">एक्शन</th>
                                                </tr>
                                            </thead>
                                            <tbody id="posterTableBody">
                                                <?php
                                                if ($result->num_rows > 0) {
                                                    while($row = $result->fetch_assoc()) {
                                                        ?>
                                                        <tr>
                                                            <td>
                                                                <img src="../banners/<?= htmlspecialchars($row['image_name']) ?>" class="portal-thumb">
                                                            </td>
                                                            <td>
                                                                <div class="fw-bold text-dark custom-truncate" title="<?= htmlspecialchars($row['title']) ?>">
                                                                    <?= htmlspecialchars($row['title']) ?>
                                                                </div>
                                                                <small class="text-muted custom-truncate" title="<?= htmlspecialchars($row['description']) ?>">
                                                                    <?= htmlspecialchars($row['description']) ?>
                                                                </small>
                                                            </td>
                                                            <td>
                                                                <span class="badge-date"><?= date('d M, Y', strtotime($row['uploaded_at'])) ?></span>
                                                            </td>
                                                            <td class="text-end">
                                                                <div class="btn-group gap-1">
                                                                    <a href="upload_poster.php?edit_id=<?= $row['id'] ?>&page=<?= $page ?>&search=<?= urlencode($search) ?>" class="btn btn-sm btn-outline-primary px-2" style="border-radius: 6px;"><i class="fas fa-edit"></i></a>
                                                                    <a href="upload_poster.php?delete_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger px-2" style="border-radius: 6px;" onclick="return confirm('क्या आप वाकई इसे हटाना चाहते हैं?')"><i class="fas fa-trash-alt"></i></a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                } else {
                                                    echo '<tr><td colspan="4" class="text-center py-5 text-muted"><i class="far fa-images d-block display-5 mb-2 text-warning"></i>कोई भी पोस्टर नहीं मिला।</td></tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- पेजिनेशन लिंक्स -->
                                    <?php if ($total_pages > 1): ?>
                                        <nav class="p-3 bg-light border-top" id="paginationNav">
                                            <ul class="pagination pagination-sm justify-content-center m-0">
                                                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="upload_poster.php?page=<?= $page-1 ?>">Prev</a>
                                                </li>
                                                <?php 
                                                $start_loop = max(1, $page - 2);
                                                $end_loop = min($total_pages, $page + 2);
                                                for ($i = $start_loop; $i <= $end_loop; $i++): ?>
                                                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                        <a class="page-link" href="upload_poster.php?page=<?= $i ?>"><?= $i ?></a>
                                                    </li>
                                                <?php endfor; ?>
                                                <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="upload_poster.php?page=<?= $page+1 ?>">Next</a>
                                                </li>
                                            </ul>
                                        </nav>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div> 
        </div> 
    </div> 

    <!-- 🌟 AJAX Live Search Script -->
    <script>
        let searchTimeout;
        function doLiveSearch(val) {
            clearTimeout(searchTimeout);
            const spinner = document.getElementById('searchSpinner');
            
            // कम से कम 2 शब्द टाइप होने पर सर्च ट्रिगर करें (या खाली होने पर रीसेट करें)
            if (val.length >= 2 || val.length === 0) {
                spinner.style.display = 'inline-block';
                
                searchTimeout = setTimeout(() => {
                    fetch('upload_poster.php?ajax_search=1&search=' + encodeURIComponent(val))
                        .then(response => response.text())
                        .then(data => {
                            document.getElementById('posterTableBody').innerHTML = data;
                            spinner.style.display = 'none';
                            
                            // लाइव सर्च के समय पेजिनेशन को छुपा दें ताकि गलत पेज पर रिफ्रेस न हो
                            const nav = document.getElementById('paginationNav');
                            if(nav) {
                                nav.style.display = (val.length > 0) ? 'none' : 'block';
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            spinner.style.display = 'none';
                        });
                }, 300); // 300ms का डिबाउंस टाइम ताकि कीबोर्ड टाइपिंग स्मूथ रहे
            }
        }

        // इमेज प्रीव्यू स्क्रिप्ट
        document.getElementById('fileInput').onchange = evt => {
            const [file] = document.getElementById('fileInput').files;
            if (file) {
                const preview = document.getElementById('imagePreview');
                preview.src = URL.createObjectURL(file);
                preview.style.display = 'inline-block';
                const oldDiv = document.getElementById('oldImgDiv');
                if(oldDiv) oldDiv.style.display = 'none';
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
$data_stmt->close();
$conn->close(); 
?>