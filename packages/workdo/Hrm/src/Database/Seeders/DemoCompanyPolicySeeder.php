<?php

namespace Workdo\Hrm\Database\Seeders;

use Workdo\Hrm\Models\CompanyPolicy;
use Illuminate\Database\Seeder;
use Workdo\Hrm\Models\Branch;
use App\Models\User;

class DemoCompanyPolicySeeder extends Seeder
{
    public function run($userId): void
    {
        if (CompanyPolicy::where('created_by', $userId)->exists()) {
            return;
        }

        if (!empty($userId)) {
            $branches = Branch::where('created_by', $userId)->pluck('id')->toArray();

            $policies = [
                ['title' => 'Code of Conduct Policy', 'description' => 'Comprehensive guidelines on professional behavior, ethical standards, workplace conduct, and employee responsibilities to maintain a respectful work environment.'],
                ['title' => 'Leave and Attendance Policy', 'description' => 'Detailed leave management procedures including annual leave, sick leave, casual leave, maternity leave, special leave, and attendance tracking requirements.'],
                ['title' => 'Work from Home Policy', 'description' => 'Guidelines for remote work arrangements including eligibility criteria, approval process, equipment provisions, communication protocols, and productivity expectations.'],
                ['title' => 'Grievance Redressal Policy', 'description' => 'Formal procedure for employee grievances addressing complaint filing, investigation process, resolution timeline, appeal mechanism, and confidentiality protection.'],
                ['title' => 'Anti-Harassment and Anti-Discrimination Policy', 'description' => 'Zero-tolerance policy against workplace harassment, discrimination, bullying, and sexual harassment with clear reporting mechanisms and support resources.'],
                ['title' => 'Health and Safety Policy', 'description' => 'Occupational health and safety standards including workplace hazard identification, emergency procedures, safety equipment, and employee wellness programs.'],
                ['title' => 'Data Protection and Privacy Policy', 'description' => 'Guidelines for handling personal and confidential employee data ensuring GDPR compliance, data security, employee privacy rights, and information access procedures.'],
                ['title' => 'Social Media and Digital Communication Policy', 'description' => 'Standards for employee use of social media, company communication platforms, digital professionalism, confidentiality protection, and online conduct expectations.'],
                ['title' => 'Performance Management Policy', 'description' => 'Framework for performance appraisals, goal setting, feedback mechanisms, development plans, performance improvement procedures, and evaluation processes.'],
                ['title' => 'Recruitment and Selection Policy', 'description' => 'Fair and transparent hiring procedures including job posting, application screening, interview process, selection criteria, and diversity and inclusion commitments.'],
                ['title' => 'Training and Development Policy', 'description' => 'Investment in employee development through training programs, skill enhancement, professional certifications, educational support, and career advancement opportunities.'],
                ['title' => 'Dress Code Policy', 'description' => 'Expectations regarding professional appearance, dress code standards for different departments, exceptions for specific roles, and guidelines for casual dress days.'],
                ['title' => 'Confidentiality and Non-Disclosure Policy', 'description' => 'Protection of trade secrets, intellectual property, client information, confidential business data, and restrictions on disclosure during and after employment.'],
                ['title' => 'Substance Abuse Policy', 'description' => 'Guidelines on drugs and alcohol use in workplace, testing procedures, rehabilitation support, disciplinary actions, and employee assistance programs.'],
                ['title' => 'Conflict of Interest Policy', 'description' => 'Disclosure requirements for potential conflicts, guidelines on outside employment, vendor relationships, gifts and entertainment, and decision-making fairness.'],
                ['title' => 'Flexible Working Hours Policy', 'description' => 'Options for flexible schedules, compressed workweeks, part-time arrangements, core hours requirements, and management approval procedures for work schedule modifications.'],
                ['title' => 'Travel and Entertainment Policy', 'description' => 'Guidelines for business travel approval, expense reimbursement, accommodation standards, meal allowances, and entertainment expense documentation requirements.'],
                ['title' => 'Workplace Conduct and Discipline Policy', 'description' => 'Expected conduct standards, progressive discipline procedures, corrective actions, suspension policy, termination conditions, and employee appeal rights.'],
                ['title' => 'Cybersecurity Policy', 'description' => 'IT security standards including password management, phishing prevention, malware protection, data backup, device security, and safe internet usage guidelines.'],
                ['title' => 'Diversity and Inclusion Policy', 'description' => 'Commitment to creating inclusive workplace respecting diversity, equal employment opportunity, minority group protections, and initiatives promoting diverse workforce.'],
            ];

            foreach ($policies as $index => $policyData) {
                CompanyPolicy::updateOrCreate(
                    [
                        'title' => $policyData['title'],
                        'created_by' => $userId
                    ],
                    [
                        'description' => $policyData['description'],
                        'branch_id' => !empty($branches) ? $branches[$index % count($branches)] : null,
                        'attachment' => 'file-sample.pdf',
                        'creator_id' => $userId,
                        'created_by' => $userId,
                    ]
                );
            }
        }
    }
}
