<?php

namespace App\Service;

use Phalcon\Di\Injectable;

class SnapshotService extends Injectable
{
    public function load()
    {
        $result = $this->db->fetchAll("SELECT * FROM snapshot");

       #$auth = $this->session->get('auth');
       #if (!is_array($auth)) {
       #    return []; // if user not logged in, display nothing
       #}

       #$userProjects = $this->userService->getUserProjects($auth['id']);

        $data = [];
        foreach ($result as $key => $val) {
           #if (!in_array($val['project_id'], $userProjects)) {
           #    continue; // current user doesn't have permission to the project
           #}

            $data[$key] = $result[$key];
        }

        return $data;
    }

    public function generate()
    {
        echo 'Snapshot generating...';

        $projects = $this->projectService->getAll();

        foreach ($projects as $project) {
            $id = $project->id;

            $row = $this->db->fetchOne("SELECT * FROM latest WHERE project_id=$id");
            if (!$row) {
                continue;
            }

            $data = json_decode($row['data'], true);

            $sql = "REPLACE INTO snapshot SET"
                . ' project_id='.       $data['project_id']
                . ',project_name='.     $data['project_name']
                . ',Genset_Status='.    $data['Genset_Status']
                . ',Emergency_Mode='.   $data['Emergency_Mode']
                . ',M_Start_Auto='.     $data['M_Start_Auto']
                . ',Total_Gen_Power='.  $data['Total_Gen_Power']
                . ',Total_mains_pow='.  $data['Total_mains_pow']
                . ',Dig_Input_1='.      $data['Dig_Input_1']
                . ',Dig_Input_0='.      $data['Dig_Input_0']
                . ',EZ_G_13='.          $data['EZ_G_13']
                . ',M_Start_Inhibit='.  $data['M_Start_Inhibit']
                . ',RTAC_Perm_Stat='.   $data['RTAC_Perm_Stat']
                . ',RTAC_Allow='.       $data['RTAC_Allow']
                . ',RTAC_Trip='.        $data['RTAC_Trip']
                . ',RTAC_Block='.       $data['RTAC_Block'];

            $this->db->execute($sql);
        }
    }
}
