<?php

final class MembershipPackageFeature extends Model
{
    public function forPackage(int $packageId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM membership_package_features WHERE package_id = :package_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['package_id' => $packageId]);
        return $stmt->fetchAll();
    }

    /**
     * Replaces the full feature list for a package — simplest correct approach
     * for a small, unordered, admin-edited list (no diffing needed).
     * @param string[] $featureTexts
     */
    public function replaceAll(int $packageId, array $featureTexts): void
    {
        $this->db->prepare('DELETE FROM membership_package_features WHERE package_id = :package_id')
            ->execute(['package_id' => $packageId]);

        $stmt = $this->db->prepare(
            'INSERT INTO membership_package_features (package_id, feature_text, sort_order) VALUES (:package_id, :text, :sort_order)'
        );

        $order = 1;
        foreach ($featureTexts as $text) {
            $text = trim($text);
            if ($text === '') {
                continue;
            }
            $stmt->execute(['package_id' => $packageId, 'text' => $text, 'sort_order' => $order++]);
        }
    }
}
