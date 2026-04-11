<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AgriHub - Add Animals</title>
    <link rel="stylesheet" href="../../css/main.css">
    <link rel="stylesheet" href="../../css/sidebar.css">
    <link rel="stylesheet" href="../../css/addanimal.css">
</head>

<body>
    <div class="full-screen">
        <aside class="left-wing">
        </aside>

        <main class="main-body">
            <header class="content-title">
                <div class="panel-name">Add Animals</div>
            </header>

            <section class="form-container">
                <form class="form-card" method="POST" action="../../../backend/add_animal_process.php">
                    <h2>Add new animal</h2>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Animal Gender</label>
                            <select name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" />
                        </div>

                        <div class="form-group">
                            <label>Current Weight (kg)</label>
                            <input type="number" name="weight" step="0.01" required />
                        </div>

                        <div class="form-group">
                            <label>Health Status</label>
                            <input type="text" name="health_status" placeholder="Healthy, Sick, etc." />
                        </div>

                        <div class="form-group">
                            <label>Sale Status</label>
                            <select name="sale_status">
                                <option value="available">Available</option>
                                <option value="reserved">Reserved</option>
                                <option value="sold">Sold</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea name="notes" rows="4"></textarea>
                    </div>

                    <button type="submit" name="save_animal" class="side-link"
                        style="border: none; cursor: pointer; width: 250px; margin-top: 20px;">Save Animal</button>
                </form>
            </section>
        </main>
    </div>
</body>

</html>