<?php

// Function to execute the curl command and check the response
function executeCurlCommand($relay) {
    global $errorLogFile;

    $command = 'curl -X POST -d "command=' . $relay . '" http://192.168.1.185/control';
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Command: ' . $command . PHP_EOL, FILE_APPEND);
    $output = shell_exec($command);
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Output: ' . $output . PHP_EOL, FILE_APPEND);
    file_put_contents($errorLogFile, date('Y-m-d H:i:s') . ' Executed command: ' . $command . ' Output: ' . $output . PHP_EOL, FILE_APPEND);
    if (strpos($output, 'Failed') !== false || strpos($output, 'error') !== false) {
        return ['status' => 'error', 'message' => 'Failed to execute curl command. Check error.log for details.'];
    }
    return ['status' => 'success'];
}

// Subroutine to control relays for pool setting
function setPool() {
    global $settings;
    $response = heaterOff();
    if ($response['status'] === 'error') {
        return $response;
    }
    
    $response = executeCurlCommand('Relay 1 on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 1 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 4 on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 4 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = pumpFast();
    if ($response['status'] === 'error') {
        return $response;
    }
    if ($settings['heater'] === 'spa') {
        $response = heaterOff();
        if ($response['status'] === 'error') {
            return $response;
        }
        return ['status' => 'warning', 'message' => 'Heater was on spa so heater has been turned off.'];
    }
    return ['status' => 'success'];
}

// Subroutine to control relays for spa setting
function setSpa() {
    $response = executeCurlCommand('Relay 2 on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 2 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand('Relay 3 on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 3 off');
    return $response;
}

// Subroutine to control relay for heater off
function heaterOff() {
    $response = executeCurlCommand('Relay 5 on');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 5 off');
    return $response;
}

// Redefined subroutine to toggle relay for heater pool
function heaterPool() {
    // Start logging
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Starting heaterPool function.' . PHP_EOL, FILE_APPEND);
    
    // Set pump to fast
    $response = pumpFast();
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' pumpFast error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Pump set to fast. Proceeding to pulse Relay 6.' . PHP_EOL, FILE_APPEND);
    
    // Pulse Relay 6
    $response = executeCurlCommand('Relay 6 on');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Relay 6 on error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Relay 6 on executed successfully.' . PHP_EOL, FILE_APPEND);
    sleep(1);
    $response = executeCurlCommand('Relay 6 off');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Relay 6 off error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Successfully executed heaterPool function.' . PHP_EOL, FILE_APPEND);
    return $response;
}

// Subroutine to toggle relay for heater spa
function heaterSpa() {
    global $settings;
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Starting heaterSpa function.' . PHP_EOL, FILE_APPEND);

    if ($settings['valve'] !== 'spa') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Valve is not set to spa. Heater spa cannot be turned on.' . PHP_EOL, FILE_APPEND);
        return ['status' => 'warning', 'message' => 'Valve is not set to spa. Heater spa cannot be turned on.'];
    }

    $response = pumpFast();
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' pumpFast error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Pump set to fast. Proceeding to turn on Relay 7.' . PHP_EOL, FILE_APPEND);
    
    $response = executeCurlCommand('Relay 7 on');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Relay 7 on error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Relay 7 on executed successfully.' . PHP_EOL, FILE_APPEND);
    sleep(1);
    $response = executeCurlCommand('Relay 7 off');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Relay 7 off error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Successfully executed heaterSpa function.' . PHP_EOL, FILE_APPEND);
    return $response;
}

// Subroutine to control relay for pump off
function pumpOff() {
    global $settings;
    $response = heaterOff();
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 10 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand('Relay 9 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    return executeCurlCommand('Relay 8 off');
}

// Subroutine to control relay for pump slow
function pumpSlow() {
    global $settings;
    $response = executeCurlCommand('Relay 10 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand('Relay 9 on');
    if ($response['status'] === 'error') {
        return $response;
    }
    if ($settings['heater'] === 'on') {
        $response = heaterOff();
        if ($response['status'] === 'error') {
            return $response;
        }
        return ['status' => 'warning', 'message' => 'Pump set to slow, so heater has been turned off.'];
    }
    return $response;
}

// Subroutine to control relay for pump fast
function pumpFast() {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Starting pumpFast function.' . PHP_EOL, FILE_APPEND);
    $response = executeCurlCommand('Relay 9 off');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Relay 9 off error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    $response = executeCurlCommand('Relay 10 on');
    if ($response['status'] === 'error') {
        file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Relay 10 on error: ' . $response['message'] . PHP_EOL, FILE_APPEND);
        return $response;
    }
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' Successfully executed pumpFast function.' . PHP_EOL, FILE_APPEND);
    return $response;
}

// Subroutine to control relays for mix setting
function setMix() {
    global $settings;
    $response = heaterOff();
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand('Relay 1 on'); // Pool inlet
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 1 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand('Relay 2 on'); // Spa inlet
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 2 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand('Relay 3 on'); // Pool outlet
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 3 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand('Relay 4 on'); // Spa outlet
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 4 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = heaterOff();
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = pumpSlow();
    if ($response['status'] === 'error') {
       return $response;
    }

    return ['status' => 'success'];
}

?>
