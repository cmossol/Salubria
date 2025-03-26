<?php

// Define an alias for Relay 1
$aliasArray = [
    'from_pool' => 'Relay 1',
    'from_spa' => 'Relay 2',
    'to_pool' => 'Relay 3',
    'to_spa' => 'Relay 4',
    'heater_off' => 'Relay 5',
    'heater_warm' => 'Relay 6',
    'heater_hot' => 'Relay 7',
    'pump_off' => 'Relay 8',
    'pump_slow' => 'Relay 9',
    'pump_fast' => 'Relay 10'
];

// Function to execute the curl command and check the response
function executeCurlCommand($data) {
    global $errorLogFile;

    $command = 'curl -X POST -d "command=' . $data . '" http://192.168.1.185/control 2>&1';
    file_put_contents("debug.log", "Executing curl command: $command\n", FILE_APPEND);
    $output = shell_exec($command);
    if (strpos($output, 'Failed') !== false || strpos($output, 'error') !== false) {
        file_put_contents($errorLogFile, date('Y-m-d H:i:s') . ' Error: Failed to execute curl command: ' . $command . ' Output: ' . $output . PHP_EOL, FILE_APPEND);
        return ['status' => 'error', 'message' => 'Failed to execute curl command. Check error.log for details.'];
    }
    file_put_contents($errorLogFile, date('Y-m-d H:i:s') . ' Activity: Successfully executed curl command: ' . $command . ' Output: ' . $output . PHP_EOL, FILE_APPEND);
    return ['status' => 'success'];
}

// change uses of first 2 parameters to use the alias array
function setPool() {
    global $settings, $aliasArray;
    $response = heaterOff();
    if ($response['status'] === 'error') {
        return $response;
    }

    $response = executeCurlCommand($aliasArray['from_pool'] . ' on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['from_pool'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(0.5);
    $response = executeCurlCommand($aliasArray['to_spa'] . ' on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(0.5);
    $response = executeCurlCommand($aliasArray['to_spa'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = pumpFast();
    if ($response['status'] === 'error') {
        return $response;
    }
    return ['status' => 'success'];
}

// Subroutine to control relays for spa setting
function setSpa() {
    global $aliasArray;
    $response = heaterOff();
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand($aliasArray['from_spa'] . ' on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['from_spa'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand($aliasArray['to_spa'] . ' on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['to_spa'] . ' off');
    return $response;
}

// Subroutine to control relay for heater off
function heaterOff() {
    global $aliasArray;
    $response = executeCurlCommand($aliasArray['heater_off'] . ' on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['heater_off'] . ' off');
    return $response;
}

// Redefined subroutine to toggle relay for heater pool
function heaterPool() {
    global $aliasArray;
    // Start logging
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Starting heaterPool function.' . PHP_EOL, FILE_APPEND);
    
    // Set pump to fast
    $response = pumpFast();
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' pumpFast error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Pump set to fast. Proceeding to pulse heater_warm.' . PHP_EOL, FILE_APPEND);
    
    // Pulse heater_warm
    $response = executeCurlCommand($aliasArray['heater_warm'] . ' on');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' heater_warm on error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' heater_warm on executed successfully.' . PHP_EOL, FILE_APPEND);
    sleep(1);
    $response = executeCurlCommand($aliasArray['heater_warm'] . ' off');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' heater_warm off error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Successfully executed heaterPool function.' . PHP_EOL, FILE_APPEND);
    return $response;
}

// Subroutine to toggle relay for heater spa
function heaterSpa() {
    global $settings, $aliasArray;

    if ($settings['valve'] !== 'spa') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Valve is not set to spa. Heater spa cannot be turned on.' . PHP_EOL, FILE_APPEND);
        return ['status' => 'warning', 'message' => 'Valve is not set to spa. Heater spa cannot be turned on.'];
    }

    $response = pumpFast();
    if ($response['status'] === 'error') {
        return $response;
    }
    
    $response = executeCurlCommand($aliasArray['heater_hot'] . ' on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['heater_hot'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    return $response;
}

// Subroutine to control relay for pump off
function pumpOff() {
    global $settings, $aliasArray;
    $response = heaterOff();
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['pump_fast'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand($aliasArray['pump_slow'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    return executeCurlCommand($aliasArray['pump_off'] . ' off');
}

// Subroutine to control relay for pump slow
function pumpSlow() {
    global $settings, $aliasArray;
    $response = executeCurlCommand($aliasArray['pump_fast'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand($aliasArray['pump_slow'] . ' on');
    if ($response['status'] === 'error') {
        return $response;
    }
    if ($settings['heater'] === 'on') {
        $response = heaterOff();
        if ($response['status'] === 'error') {
            return $response;
        }
        return ['status' => 'warning', 'message' => 'Pump was set to slow, so heater has been turned off.'];
    }
    return $response;
}

// Subroutine to control relay for pump fast
function pumpFast() {
    global $aliasArray;
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Starting pumpFast function.' . PHP_EOL, FILE_APPEND);
    $response = executeCurlCommand($aliasArray['pump_slow'] . ' off');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' pump_slow off error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    $response = executeCurlCommand($aliasArray['pump_fast'] . ' on');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' pump_fast on error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Successfully executed pumpFast function.' . PHP_EOL, FILE_APPEND);
    return $response;
}

// Subroutine to control relays for mix setting
function setMix() {
    global $settings, $aliasArray;
    $response = heaterOff();
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = pumpSlow();
    if ($response['status'] === 'error') {
       return $response;
    }
    $response = executeCurlCommand($aliasArray['from_pool'] . ' on'); // Pool inlet
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['from_pool'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand($aliasArray['from_spa'] . ' on'); // Spa inlet
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['from_spa'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand($aliasArray['to_pool'] . ' on'); // Pool outlet
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['to_pool'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand($aliasArray['to_spa'] . ' on'); // Spa outlet
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand($aliasArray['to_spa'] . ' off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = heaterOff();
    if ($response['status'] === 'error') {
        return $response;
    }

    return ['status' => 'success'];
}

?>
