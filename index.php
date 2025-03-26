<?php
require_once 'subroutines.php';

session_start();

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON file to store current settings
$settingsFile = "pool_settings.json";
$errorLogFile = "error.log";

// Default settings
$defaultSettings = [
    "valve" => "pool",
    "heater" => "off",
    "pump" => "off"
];

// Load current settings
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    if (!is_array($settings)) {
        $settings = $defaultSettings;
    }
} else {
    $settings = $defaultSettings;
    file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT));
}

// Handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' POST request received.' . PHP_EOL, FILE_APPEND);
    if (isset($_POST["setting"]) && isset($_POST["value"])) {
        $setting = htmlspecialchars($_POST["setting"]);
        $value = htmlspecialchars($_POST["value"]);

        file_put_contents('debug.log', date('Y-m-d H:i:s') . " Received setting: $setting with value: $value" . PHP_EOL, FILE_APPEND);

        // Update settings
        if (array_key_exists($setting, $settings)) {
            // Check if the heater is set to spa while the valve is set to pool
            if ($setting === "valve" && $value === "pool" && $settings["heater"] === "spa") {
                // Turn off the heater and give a warning
                $response = heaterOff();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => false, "message" => "Heater set to spa while valve is set to pool. Heater turned off to prevent overheating."]);
                }
                exit;
            }

            // Check if the heater is set to pool or spa while the pump was not set to fast
            if ($setting === "heater" && ($value === "pool" || $value === "spa") && $settings["pump"] !== "fast") {
                $pumpResponse = pumpFast();
                if ($pumpResponse['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $pumpResponse['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Pump is now set to fast"]);
                }
                exit;
            }

            // Check if the heater is set to spa while the valve is set to pool
            if ($setting === "heater" && $value === "spa" && $settings["valve"] === "pool") {
                $response = heaterOff();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => false, "message" => "Valve set to pool while heater is set to spa. Heater turned off to prevent overheating."]);
                }
                exit;
            }

            $settings[$setting] = $value;
            file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT)); // Save the state to settings.json

            // If the pool button is clicked, call the pool subroutine
            if ($setting === "valve" && $value === "pool") {
                $response = setPool();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Pool subroutine executed successfully"]);
                }
                exit;
            } elseif ($setting === "valve" && $value === "spa") {
                $response = setSpa();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Spa subroutine executed successfully"]);
                }
                exit;
            } elseif ($setting === "valve" && $value === "mix") {
                $response = setMix();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else if ($response['status'] === 'warning') {
                    echo json_encode(["success" => true, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Mix subroutine executed successfully"]);
                }
                exit;
            } elseif ($setting === "heater" && $value === "off") {
                $response = heaterOff();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Heater off subroutine executed successfully"]);
                }
                exit;
            } elseif ($setting === "heater" && $value === "pool") {
                $response = heaterPool();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Heater pool subroutine executed successfully"]);
                }
                exit;
            } elseif ($setting === "heater" && $value === "spa") {
                $response = heaterSpa();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Heater spa subroutine executed successfully"]);
                }
                exit;
            } elseif ($setting === "pump" && $value === "off") {
                $response = pumpOff();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else if ($response['status'] === 'warning') {
                    echo json_encode(["success" => true, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Pump off subroutine executed successfully"]);
                }
                exit;
            } elseif ($setting === "pump" && $value === "slow") {
                $response = pumpSlow();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Pump slow subroutine executed successfully"]);
                }
                exit;
            } elseif ($setting === "pump" && $value === "fast") {
                $response = pumpFast();
                if ($response['status'] === 'error') {
                    echo json_encode(["success" => false, "message" => $response['message']]);
                } else {
                    echo json_encode(["success" => true, "message" => "Pump fast subroutine executed successfully"]);
                }
                exit;
            } else {
                // Construct curl command
                $url = "http://192.168.1.185/control";
                $cmd = "curl -X POST -d 'setting=$setting&value=$value' $url";

                // Execute curl command
                exec($cmd, $output, $return_var);

                // Send the response back to the client
                if ($return_var === 0) {
                    echo json_encode(["success" => true, "message" => "Received setting: $setting with value: $value", "response" => implode("\n", $output)]);
                } else {
                    echo json_encode(["success" => false, "message" => "Curl command failed", "response" => implode("\n", $output)]);
                }
                exit;
            }
        }
    }

    // Handle missing or invalid parameters
    echo json_encode(["success" => false, "message" => "Missing 'setting' or 'value' in POST request."]);
    exit;
}

// Serve HTML for GET requests
if ($_SERVER["REQUEST_METHOD"] === "GET") {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salubria.com</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
        }
        h1 {
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 30px;
        }
        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        button {
            padding: 10px 20px;
            border: 2px solid black;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            color: white;
            transition: background 0.3s;
        }
        button.blue { background: blue; }
        button.pink { background: pink; color: black; }
        button.red { background: red; }
        button.grey { background: grey; }
        button.yellow { background: yellow; color: black; }
        button.green { background: green; }
        button:focus {
            outline: none;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.4);
        }
        .active {
            border: 4px solid black;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Salubria</h1>

        <div class="section">
            <h2>Valve Settings</h2>
            <div class="button-group" id="valve">
                <button data-setting="valve" data-value="pool" class="blue">Pool</button>
                <button data-setting="valve" data-value="mix" class="pink">Mix</button>
                <button data-setting="valve" data-value="spa" class="red">Spa</button>
            </div>
        </div>

        <div class="section">
            <h2>Heater</h2>
            <div class="button-group" id="heater">
                <button data-setting="heater" data-value="off" class="grey">Off</button>
                <button data-setting="heater" data-value="pool" class="pink">Pool</button>
                <button data-setting="heater" data-value="spa" class="red">Spa</button>
            </div>
        </div>

        <div class="section">
            <h2>Pump</h2>
            <div class="button-group" id="pump">
                <button data-setting="pump" data-value="off" class="grey">Off</button>
                <button data-setting="pump" data-value="slow" class="yellow">Slow</button>
                <button data-setting="pump" data-value="fast" class="green">Fast</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const buttons = document.querySelectorAll(".button-group button");

            buttons.forEach(button => {
                button.addEventListener("click", () => {
                    // Remove "active" class from all buttons in the same group
                    const group = button.closest(".button-group").querySelectorAll("button");
                    group.forEach(btn => btn.classList.remove("active"));

                    // Add "active" class to the clicked button
                    button.classList.add("active");

                    // Send AJAX request to update setting
                    const setting = button.getAttribute("data-setting");
                    const value = button.getAttribute("data-value");

                    fetch("", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams({ setting, value })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log(`Successfully updated ${setting} to ${value}`);
                        } else {
                            console.error("Failed to update setting:", data.message);
                            if (data.message.includes("Heater turned off")) {
                                alert(data.message);
                            } else if (data.message.includes("Pump is now set to fast")) {
                                alert(data.message);
                            }
                        }
                    })
                    .catch(error => console.error("Error:", error));
                });
            });

            // Set the active button based on current settings
            const currentSettings = <?php echo json_encode($settings); ?>;
            for (const setting in currentSettings) {
                const value = currentSettings[setting];
                const button = document.querySelector(`button[data-setting="${setting}"][data-value="${value}"]`);
                if (button) {
                    button.classList.add("active");
                }
            }
        });
    </script>
</body>
</html>
<?php
    exit;
}
?>
