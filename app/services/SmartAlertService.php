<?php

namespace App\Service;

use Phalcon\Di\Injectable;

class SmartAlertService extends Injectable
{
    protected $alerts;

    public function run()
    {
        echo "Smart Alert is running ...", EOL;

        $this->alerts = [];

        $this->checkStatusChanged();

        if ($this->alerts) {
            $this->saveAlerts();
            $this->sendAlerts();
        }
    }

    protected function checkStatusChanged()
    {
        $rows = $this->db->fetchAll("SELECT * FROM status_change");

        foreach ($rows as $row) {
            if ($row['checked']) {
                continue;
            }

            $project = $this->projectService->get($row['project_id']);
            $projectName = $project->name;

            $genPowerOld = abs($row['gen_power_old']);
            $genPowerNew = abs($row['gen_power_new']);

            if (abs($genPowerNew - $genPowerOld) > max($genPowerNew, $genPowerOld)/2) {
                $subject = "GCP Alert: $projectName - Generator Power Changed";
                $this->log($subject);
                $this->log(print_r($row, true));
                $this->alerts[] = [
                    'subject' => $subject,
                    'project' => $project,
                    'data' => $row,
                ];
            }

            $storeLoadOld = abs($row['store_load_old']);
            $storeLoadNew = abs($row['store_load_new']);

            if (abs($storeLoadNew - $storeLoadOld) max($storeLoadNew, $storeLoadOld)/2) {
                $subject = "GCP Alert: $projectName - Store Load Changed";
                $this->log($subject);
                $this->log(print_r($row, true));
                $this->alerts[] = [
                    'subject' => $subject,
                    'project' => $project,
                    'data' => $row,
                ];
            }
        }

        $this->db->execute("UPDATE status_change SET checked=1");
    }

    protected function generateHtml($alerts)
    {
        ob_start();
        include(BASE_DIR . "/job/templates/status-change.tpl");
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    protected function saveAlerts()
    {
        /*
        foreach ($this->alerts as $alert) {
            try {
                $this->db->insertAsDict('smart_alert_log', [
                    'time'         => $alert['time'],
                    'project_id'   => $alert['project_id'],
                    'alert'        => $alert['alert'],
                    'message'      => $alert['message'],
                ]);
            } catch (\Exception $e) {
                echo $e->getMessage(), EOL;
            }
        }
        */
    }

    protected function sendAlerts()
    {
        $users = $this->userService->getAll();

        foreach ($users as $user) {
           #if ($user['id'] > 1) break;

            if (strpos($user['email'], '@') === false) {
                continue;
            }

            $html = $this->generateHtml($this->alerts);
            $subject = $this->getSubject($this->alerts);

            $this->sendEmail($user['email'], $subject, $html);
        }
    }

    protected function getSubject($alerts)
    {
        $alert = $alerts[0];
        return $alert['subject'];
    }

    protected function sendEmail($recepient, $subject, $body)
    {
        if (!$this->emailService->send($recepient, $subject, $body)) {
            $this->log("Mailer Error: " . $this->emailService->getErrorInfo());
        } else {
            $this->log("Smart Alert sent to $recepient.");
        }
    }

    protected function log($str)
    {
        return;
        $filename = BASE_DIR . '/app/logs/alert.log';
        $str = date('Y-m-d H:i:s ') . $str . "\n";

        echo $str;
        error_log($str, 3, $filename);
    }
}
