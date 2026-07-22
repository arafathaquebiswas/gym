<?php

final class PageController extends Controller
{
    public function about(): void
    {
        $trainerModel = new Trainer();
        $this->view('about', ['trainers' => $trainerModel->allActive()]);
    }

    public function membership(): void
    {
        $packageModel = new Package();
        $featureModel = new MembershipPackageFeature();
        $trainerModel = new Trainer();
        $faqModel = new Faq();

        $packages = $packageModel->allActive();
        foreach ($packages as &$pkg) {
            $pkg['features'] = $featureModel->forPackage((int) $pkg['id']);
        }
        unset($pkg);

        // Union of every distinct feature text across packages, in first-seen order — the comparison table's rows.
        $comparisonFeatures = [];
        foreach ($packages as $pkg) {
            foreach ($pkg['features'] as $feature) {
                $comparisonFeatures[$feature['feature_text']] = true;
            }
        }

        $this->view('membership', [
            'packages' => $packages,
            'comparisonFeatures' => array_keys($comparisonFeatures),
            'liveOffers' => array_filter($packages, fn ($p) => $p['offer_is_live']),
            'trainers' => $trainerModel->allActive(),
            'faqs' => $faqModel->byCategories(['membership', 'pricing']),
        ]);
    }

    public function personalTraining(): void
    {
        $trainerModel = new Trainer();
        $galleryModel = new GalleryItem();
        $this->view('personal-training', [
            'trainers' => $trainerModel->allActive(),
            'teamPhotos' => $galleryModel->all('team'),
        ]);
    }

    public function faq(): void
    {
        $faqModel = new Faq();
        $this->view('faq', ['faqs' => $faqModel->allActive()]);
    }
}
