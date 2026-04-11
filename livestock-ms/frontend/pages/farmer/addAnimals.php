<?php 
// Include config just to access the session started there
require_once '../../../backend/db_config.php'; 
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub - Add Animals</title>

    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/addanimal.css">

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Arvo:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet" />
</head>

<body>
    <div class="full-screen">
        <aside class="left-wing">
            <div class="logo-panel">
                <div class="logo">AgriHub</div>
                <div class="panel-description">Farmers Portal</div>
            </div>

            <nav class="side-bar">
                <ul>
                    <li><a href="dashboard.php" class="side-link">Dashboard</a></li>
                    <li><a href="mylivestock.php" class="side-link">My Livestock</a></li>
                    <li><a href="addAnimals.php" class="side-link">Add Animals</a></li>
                    <li><a href="weightLog.php" class="side-link">Weight Log</a></li>
                    <li><a href="myFarm.php" class="side-link">My Farm</a></li>
                    <li><a href="orderRecieved.php" class="side-link">Order Received</a></li>
                    <li><a href="transaction.php" class="side-link">Transactions</a></li>
                </ul>
            </nav>

            <div class="basic-profile">
                <div class="pfp">AJ</div>
                <div>
                    <div class="username">Austien James</div>
                    <div class="designation">Farmer</div>
                </div>
            </div>
        </aside>

        <main class="main-body">
            <header class="content-title">
                <div class="panel-name">Add Animals</div>
                <div class="des-pfp">
                    <div class="designation-icon">Farmer</div>
                    <div class="pfp-2">AJ</div>
                </div>
            </header>

            <section class="form-container">
                <form class="form-card" method="POST" action="../../../backend/add_animal_process.php">
                    <h2>Add new animal</h2>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Animal name / tag</label>
                            <input type="text" name="animal_name" required />
                        </div>

                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" required class="form-group">
                                <option value="1">Pig</option>
                                <option value="2">Cow</option>
                                <option value="3">Chicken</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Breed</label>
                            <input type="text" name="breed" />
                        </div>

                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required class="form-group">
                                <option value=" male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Date of birth</label>
                            <input type="date" name="dob" />
                        </div>

                        <div class="form-group">
                            <label>Current weight (kg)</label>
                            <input type="number" step="0.01" name="weight" />
                        </div>

                        <div class="form-group">
                            <label>Health status</label>
                            <input type="text" name="health" />
                        </div>

                        <div class="form-group">
                            <label>Sale status</label>
                            <select name="sale_status" class="form-group">
                                <option value="available">Available</option>
                                <option value="reserved">Reserved</option>
                                <option value="sold">Sold</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Location / farm</label>
                        <input type="text" name="location" />
                    </div>

                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea name="notes" rows="4"></textarea>
                    </div>

                    <button type="submit" name="save_animal" class="side-link save-animal-button">Save
                        Animal</button>
                </form>
            </section>
        </main>
    </div>
</body>

</html>