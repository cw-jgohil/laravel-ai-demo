<?php

namespace Database\Seeders;

use App\Models\Protocol;
use Illuminate\Database\Seeder;

class ProtocolSeeder extends Seeder
{
    public function run(): void
    {
        Protocol::query()->truncate();

        Protocol::create([
            'title' => 'Allergic Reaction - Adult',
            'description' => 'Assessment and treatment for mild to severe allergic reactions including anaphylaxis. Consider epinephrine IM for anaphylaxis, antihistamines for mild reactions, airway monitoring, and rapid transport.',
        ]);

        Protocol::create([
            'title' => 'Respiratory Distress - Dyspnea',
            'description' => 'Patients with shortness of breath due to asthma, COPD, CHF, pneumonia, or anaphylaxis. Consider oxygen, bronchodilators, CPAP as indicated, and continuous reassessment.',
        ]);

        Protocol::create([
            'title' => 'Chest Pain - Suspected ACS',
            'description' => 'Patients with acute chest pain or pressure suspicious for ACS. Administer aspirin if not contraindicated, nitroglycerin with BP > 100 systolic, consider 12-lead ECG, and transport rapidly.',
        ]);
    }
}


