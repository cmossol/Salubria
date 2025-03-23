<?php

// Function to execute the curl command and check the response
function executeCurlCommand($relay) {
    global $errorLogFile;

    $command = 'curl -X POST -d "command=' . $relay . ' on" http://192.168.1.185/control';
    $output = shell_exec($command);
    if (strpos($output, 'Failed') !== false || strpos($output, 'error') !== false) {
        file_put_contents($errorLogFile, date('Y-m-d H:i:s') . ' Error: Failed to execute curl command: ' . $command . ' Output: ' . $output . PHP_EOL, FILE_APPEND);
        return ['status' => 'error', 'message' => 'Failed to execute curl command. Check error.log for details.'];
    }
    file_put_contents($errorLogFile, date('Y-m-d H:i:s') . ' Activity: Successfully executed curl command: ' . $command . ' Output: ' . $output . PHP_EOL, FILE_APPEND);
    return ['status' => 'success'];
}

// Subroutine to control relays for pool setting
function setPool() {
    global $settings;
    $response = executeCurlCommand('Relay 1');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(2);
    $response = executeCurlCommand('Relay 1 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 4');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(2);
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
        return ['status' => 'warning', 'message' => 'Heater was on spa and has been turned off. Pump is set to fast.'];
    }
    return ['status' => 'success'];
}

// Subroutine to control relays for spa setting
function setSpa() {
    $response = executeCurlCommand('Relay 2');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(2);
    $response = executeCurlCommand('Relay 2 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 3');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(2);
    $response = executeCurlCommand('Relay 3 off');
    return $response;
}

// Subroutine to control relay for heater off
function heaterOff() {
    $response = executeCurlCommand('Relay 5');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(0.5);
    $response = executeCurlCommand('Relay 5 off');
    return $response;
}

// Subroutine to control relay for heater pool
function heaterPool() {
    $response = executeCurlCommand('Relay 6');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(0.5);
    $response = executeCurlCommand('Relay 6 off');
    return $response;
}

// Subroutine to control relay for heater spa
function heaterSpa() {
    $response = executeCurlCommand('Relay 7');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(0.5);
    $response = executeCurlCommand('Relay 7 off');
    return $response;
}

// Subroutine to control relay for pump off
function pumpOff() {
    global $settings;
    if ($settings['heater'] !== 'off') {
        $response = heaterOff();
        if ($response['status'] === 'error') {
            return $response;
        }
        sleep(5);
    }
    $response = executeCurlCommand('Relay 8');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(1);
    $response = executeCurlCommand('Relay 8 off');
    return $response;
}

// Subroutine to control relay for pump slow
function pumpSlow() {
    $response = executeCurlCommand('Relay 10 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand('Relay 9');
    return $response;
}

// Subroutine to control relay for pump fast
function pumpFast() {
    $response = executeCurlCommand('Relay 9 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = executeCurlCommand('Relay 10');
    return $response;
}

// Subroutine to control relays for mix setting
function setMix() {
    $response = executeCurlCommand('Relay 1');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(4);
    $response = executeCurlCommand('Relay 1 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(2);
    $response = executeCurlCommand('Relay 2');
    if ($response['status'] === 'error') {
        return $response;
    }
    sleep(2);
    $response = executeCurlCommand('Relay 2 off');
    if ($response['status'] === 'error') {
        return $response;
    }
    $response = heaterOff();
    return $response;
}

?>
