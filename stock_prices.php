<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">

    <title>stock market values</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Shantell+Sans:ital,wght@1,300&display=swap');
        body {
        background: #f2f2f2;
        font-family: 'Shantell Sans', cursive;
        }


        #main {
        width: 500px;
        margin: 50px auto;
        padding: 30px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        form {
        display: flex;
        flex-direction: column;
        }

        label {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 5px;
        color: #444;
        }

        select, input[type="date"] {
        width: 100%;
        padding: 10px;
        border-radius: 5px;
        border: none;
        margin-bottom: 15px;
        font-size: 16px;
        color: #555;
        }

        input[type="submit"] {
        background: #3f51b5;
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s ease;
        }

        input[type="submit"]:hover {
        background: #303f9f;
        }

        h2{
            font-family: 'Shantell Sans', cursive;
            text-align: center;
            margin-bottom: 50px;
            margin-top: 15px;
            font-size: 40px;
        }
    </style>
</head>
<body>
    <?php if(!isset($_POST['submit'])){ ?>
        <div class="main" id="main">
            <form method="post">
                <label for="c_name">company Name</label><br>
                <select name="c_name" id="c_name"><br>
                    <option value="PIRC.MI">pirelli</option>
                    <option value="%5EIXIC">nasdaq</option>
                    <option value="NVDA">Nvida</option>
                    <option value="TSLA">Tesla</option>
                    <option value="DIS">Walt Disney</option>
                    <option value="AMZN">Amazon</option>
                    <option value="AAPL">Apple</option>
                </select><br>
                <label for="frequency">Period of data</label><br>
                <select name="frequency" id="frequency"><br>
                    <option value="1d">daily</option>
                    <option value="1wk">weekly</option>
                    <option value="1mo">monthly</option>
                </select><br>
                <label for="from">From</label><br>
                <input type="date" name="from" id="from" required><br>
                <label for="to">To</label><br>
                <input type="date" name="to" id="to" required><br><br>
                <input type="submit" name="submit" value="submit">
            </form>
        </div>
    <?php } ?>   
    

    <?php
        if(isset($_POST['submit'])){
            $company_name=$_POST['c_name'];
            $frequency=$_POST['frequency'];
            $date_from=$_POST['from'];
            $date_to=$_POST['to'];
            $milliseconds_from =strtotime($date_from);
            $milliseconds_to =strtotime($date_to);

            if($milliseconds_to < $milliseconds_from) {
                $message = "date insert isn't correct";
                echo "<script type='text/javascript'>alert('$message');</script>";
            }
            
            $url="https://query1.finance.yahoo.com/v7/finance/download/$company_name?period1=$milliseconds_from&period2=$milliseconds_to&interval=$frequency&events=history&includeAdjustedClose=true";
            // echo $url;
            $file_name =$company_name.".csv";
            $file=file_get_contents($url);
            file_put_contents($file_name, $file);

            csv_to_json($file_name,$company_name);
            csv_to_db($file_name,$company_name);
        
        }
        
        function csv_to_json($fname,$company_name) {

            // open csv file
            if (!($fp = fopen($fname, 'r'))) {
                die("Can't open file...");
            }
            
            //read csv headers
            $header = fgetcsv($fp,"1024",",");
            
            // parse csv rows into array
            $json = array();
                while ($row = fgetcsv($fp,"1024",",")) {
                $json[] = array_combine($header, $row);
            }
            
            // close file handle
            fclose($fp);

            //write to JSON file
            $fpn = fopen($company_name.".json", 'w');
            fwrite($fpn, json_encode($json, JSON_PRETTY_PRINT));
            fclose($fpn);
            
        }

        function csv_to_db($file_name,$company_name){
            $servername = "localhost";
            $username = "root";
            $password = "";
            $database = "ETL_DB";
            $first_table = "Titoli_tb";
            $second_table = "Dati_tb";

            // Create connection
            $conn = new mysqli($servername, $username, $password);

            // Check connection
            if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
            }

            $sql = "CREATE DATABASE IF NOT EXISTS $database";
            
            if ($conn->query($sql) === true) {
            } else {
                die("Error creating database: " . $conn->error);
            }
            // Connect to the newly created database
            $conn->close();
            
            //connection
            $conn = new mysqli($servername, $username, $password, $database);

            //query
            $sql = "CREATE TABLE IF NOT EXISTS $first_table (
                id_titolo int auto_increment primary key,
                nome varchar(50),
                descrizione varchar(50) ,
                cod varchar(16) UNIQUE,
                data_inizio date,
                data_fine date
            )";

            if ($conn->query($sql) === true) {
            } else {
                die("Error creating table: " . $conn->error);
            }

            $sql = "CREATE TABLE IF NOT EXISTS $second_table (
                id_titolo int not null,
                Date_data date not null,
                open_values double NOT NULL,
                High_values double NOT NULL,
                low_values double NOT NULL,
                close_values double NOT NULL,
                adj_close_values double NOT NULL,
                volume_values int NOT NULL,
                foreign key (id_titolo) references Titoli_tb(id_titolo)
            )";
   
            if ($conn->query($sql) === true) {
            } else {
                die("Error creating table: " . $conn->error);
            }

            $stmt =$conn->prepare( "INSERT IGNORE INTO $first_table (id_titolo,nome,descrizione,cod) VALUES (?,?,?,?)");
            $stmt ->bind_param("ssss",$id_titolo,$nome,$descrizione,$cod);

            $nome="Pirelli";
            $descrizione="mercato azionario pirelli milano";
            $cod="PIRC.MI";
            $stmt->execute();

            $nome="Nasdaq";
            $descrizione="mercato azionario nasdaq milano";
            $cod="%5EIXIC";
            $stmt->execute();
            
            $nome="Nvida";
            $descrizione="mercato azionario nvida corporation";
            $cod="NVDA";
            $stmt->execute();
            
            $nome="Tesla";
            $descrizione="mercato azionario tesla corporation";
            $cod="TSLA";
            $stmt->execute();

            $nome="Walt Disney";
            $descrizione="mercato azionario walt disney";
            $cod="DIS";
            $stmt->execute();

            $nome="Amazon";
            $descrizione="mercato azionario Amazon";
            $cod="AMZN";
            $stmt->execute();

            $nome="Apple";
            $descrizione="mercato azionario Apple california";
            $cod="AAPL";
            $stmt->execute();

            $stmt->close();

            $sql="SELECT id_titolo from $first_table where cod ='$company_name' ";
            $result = $conn->query($sql);
            //var_dump($result);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $codice = $row['id_titolo'];
                }
            }

            $array = []; 
            // Open the CSV file
            if (($handle = fopen($file_name, "r")) !== FALSE) {
                // Loop through each row of the CSV file
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $array[] = $data;
                    $stmt = $conn->prepare("INSERT IGNORE INTO $second_table (id_titolo,Date_data,Open_values,High_values,Low_values,Close_values,Adj_Close_values,Volume_values) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->bind_param("isssssss",$codice,$data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6]);
                    $stmt->execute();
                    $stmt->close();
                }
                fclose($handle);
                $lung_array = count($array); //count the length of array
            }
            $conn->close();

            csv_to_display($array,$lung_array);
        }
        /*
        // Prendi l'id del titolo della compagnia selezionata
        $sql = "SELECT id_titolo from $first_table where cod ='$company_name' ";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $codice = $row['id_titolo'];
            }
        }

        // Aggiorna la data di inizio e la data di fine per il titolo selezionato
        $sql = "UPDATE $first_table SET data_inizio=?, data_fine=? WHERE id_titolo=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $data_inizio, $data_fine, $codice);
        $stmt->execute();
        $stmt->close();*/
        function csv_to_display($array,$lung_array){
            echo "<h2>Displaying the saved data</h2>";
            echo "<!-- Bootstrap table -->";
            echo "<div class='container'>";
                echo "<table class='table table-striped'>";
                    echo "<thead class='thead-dark'>";
                        echo "<tr>";
                            for ($i = 0; $i < count($array[0]); $i++) { //loop for th
                                echo "<th>" . ($array[0][$i]) . "</th>";
                            }
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                        for ($i = 1; $i < $lung_array; $i++) { //loop for tr
                            echo "<tr>";
                            for ($j = 0; $j < count($array[$i]); $j++) { // loop for td
                                echo "<td>" . $array[$i][$j] . "</td>";
                            }
                            echo "</tr>";
                        }
                    echo"</tbody>";
                echo"</table>";
            echo"</div>";
        }

    ?>

</body>
</html>