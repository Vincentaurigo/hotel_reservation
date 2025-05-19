<?php
include '../config/connection.php';
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>Unauthorized access!</div>";
    exit;
}

// Check if ID is provided and numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>Invalid room ID!</div>";
    exit;
}

$id = $_GET['id'];

// Get room data
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger'>Room not found!</div>";
    exit;
}

$room = $result->fetch_assoc();
?>

<form action="update_room.php" method="post" enctype="multipart/form-data" class="edit-form">
    <input type="hidden" name="id" value="<?php echo $room['id']; ?>">
    
    <div class="form-group">
        <label for="name">Nama Kamar:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($room['name']); ?>" required>
    </div>
    
    <div class="form-group">
        <label for="price">Harga (Rp):</label>
        <input type="number" id="price" name="price" value="<?php echo $room['price']; ?>" required>
    </div>
    
    <div class="form-group">
        <label for="facilities">Fasilitas:</label>
        <textarea id="facilities" name="facilities" rows="3" required><?php echo htmlspecialchars($room['facilities']); ?></textarea>
    </div>
    
    <div class="form-group">
        <label>Gambar Saat Ini:</label>
        <div class="current-image">
            <img src="../uploads/<?php echo $room['image']; ?>" alt="<?php echo $room['name']; ?>">
        </div>
    </div>
    
    <div class="form-group">
        <label for="image">Ganti Gambar (Opsional):</label>
        <input type="file" id="image" name="image" accept="image/*">
    </div>
    
    <div class="form-actions">
        <button type="button" class="btn btn-cancel" onclick="closeEditModal()">
            <i class="fas fa-times"></i> Batal
        </button>
        <button type="submit" class="btn btn-save">
            <i class="fas fa-save"></i> Simpan Perubahan
        </button>
    </div>
</form>

<style>
    .edit-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .form-group label {
        font-weight: 500;
        color: #333;
    }
    
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group textarea {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    
    .form-group input[type="text"]:focus,
    .form-group input[type="number"]:focus,
    .form-group textarea:focus {
        border-color: #4361ee;
        outline: none;
        box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
    }
    
    .current-image {
        width: 100%;
        text-align: center;
        margin: 10px 0;
    }
    
    .current-image img {
        max-width: 200px;
        max-height: 150px;
        border-radius: 4px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 10px;
    }
    
    .btn-cancel {
        background-color: #6c757d;
        color: white;
    }
    
    .btn-cancel:hover {
        background-color: #5a6268;
    }
    
    .btn-save {
        background-color: #4361ee;
        color: white;
    }
    
    .btn-save:hover {
        background-color: #3a53cc;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    .btn-save:active {
        transform: translateY(0);
    }
</style>