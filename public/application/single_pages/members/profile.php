<?php

/**
 * @project:   ConcreteCMS Community
 *
 * @copyright  (C) 2021 Portland Labs (https://www.portlandlabs.com)
 * @author     Fabian Bitter (fabian@bitter.de)
 */

defined('C5_EXECUTE') or die("Access Denied.");

use Concrete\Core\Attribute\Category\UserCategory;
use Concrete\Core\Entity\File\File;
use Concrete\Core\Entity\File\Version;
use Concrete\Core\Entity\Package;
use Concrete\Core\Form\Service\Form;
use Concrete\Core\Form\Service\Widget\UserSelector;
use Concrete\Core\Html\Service\Html;
use Concrete\Core\Localization\Service\Date;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Support\Facade\Url;
use Concrete\Core\User\Group\Group;
use PortlandLabs\CommunityBadges\User\Point\Entry;
use Concrete\Core\User\User;
use Concrete\Core\User\UserInfo;
use Concrete\Core\View\View;
use Concrete\Core\Config\Repository\Repository;
use Doctrine\DBAL\Connection;
use HtmlObject\Element;
use HtmlObject\Image;

/** @var Group[] $badges */
/** @var View $view */
/** @var bool $canEdit */
/** @var UserInfo $profile */

$app = Application::getFacadeApplication();
/** @var  Date $dateHelper */
$dateHelper = $app->make(Date::class);
/** @var UserCategory $userCategory */
$userCategory = $app->make(UserCategory::class);
/** @var Repository $config */
$config = $app->make(Repository::class);
/** @var Form $form */
$form = $app->make(Form::class);
/** @var UserSelector $userSelector */
$userSelector = $app->make(UserSelector::class);
/** @var PackageService $packageService */
$packageService = $app->make(PackageService::class);
/** @var Html $htmlServer */
$htmlServer = $app->make(Html::class);
/** @var Connection $db */
$db = $app->make(Connection::class);

$earnBadgesPageId = (int)$config->get("concrete_cms_theme.earn_badges_page_id");
$earnBadgesPage = Page::getByID($earnBadgesPageId);

$user = new User();
$totalMessages = $profile->getAttribute('forums_total_posts');
$isOwnProfile = $profile->getUserID() == $user->getUserID();

$isCommunityAwardsModuleInstalled = $packageService->getByHandle("community_badges") instanceof Package;
$isCertificationsModuleInstalled = $packageService->getByHandle("certification") instanceof Package;

if ($isCommunityAwardsModuleInstalled) {
    $communityBadgesPackageEntity = $packageService->getByHandle("community_badges");
    /** @var \Concrete\Core\Package\Package $communityBadgesPackage */
    $communityBadgesPackage = $communityBadgesPackageEntity->getController();
    $jsFile = $communityBadgesPackage->getRelativePath() . "/blocks/community_badges/view.js";
    View::getInstance()->addFooterItem($htmlServer->javascript($jsFile));
}

?>

<div class="public-profile">
    <div class="profile-header-image">
        <?php
        $headerImage = $profile->getAttribute("header_image");
        if ($headerImage instanceof File) {
            $fileVersion = $headerImage->getApprovedVersion();
            if ($fileVersion instanceof Version) {
                echo '<img src="' . $fileVersion->getURL() . '" alt="' . h(t("Header Image of %s", $profile->getUserName())) . '">';
            }
        }
        ?>
    </div>

    <div class="container">
        <div class="row">
            <div class="col">
                <div class="profile-meta">
                    <div class="profile-image">
                        <div class="image-wrapper">
                            <?php echo $profile->getUserAvatar()->output(); ?>
                        </div>
                    </div>

                    <div class="profile-intro">
                        <?php if ($isCommunityAwardsModuleInstalled) { ?>
                            <?php
                            /** @var \PortlandLabs\CommunityBadges\AwardService $awardService */
                            $awardService = $app->make(\PortlandLabs\CommunityBadges\AwardService::class);
                            $totalAchievements = count($awardService->getAllAchievementsByUser($profile->getUserObject()));
                            $communityPoints = Entry::getTotal($profile);
                            /** @noinspection PhpUnhandledExceptionInspection */
                            echo t(
                                '%s has posted %s, been awarded %s and has accumulated %s since joining the community on %s.',
                                $profile->getUserName(),
                                sprintf("<b>%s</b>", t2("%s message", "%s messages", number_format($totalMessages))),
                                sprintf("<a href=\"%s\"><strong>%s</strong></a>", (string)Url::to("/account/karma", $profile->getUserID()), t2("%s achievement", "%s achievements", number_format($totalAchievements))),
                                sprintf("<a href=\"%s\"><strong>%s</strong></a>", (string)Url::to("/account/karma", $profile->getUserID()), t2("%s karma point", "%s karma points", number_format($communityPoints))),
                                $dateHelper->formatDate($profile->getUserDateAdded(), true)
                            ); ?>
                        <?php } ?>
                    </div>

                    <div class="profile-username">
                        <h1>
                            <?php
                            $userDisplayName = (string)$profile->getAttribute('first_name') . " " . (string)$profile->getAttribute('last_name');
                            if (strlen(trim($userDisplayName)) === 0) {
                                $userDisplayName = $profile->getUserName();
                            }
                            echo $userDisplayName;
                            ?>
                        </h1>

                        <div class="profile-user-actions">
                            <div class="float-right">
                                <?php if ($isCommunityAwardsModuleInstalled) { ?>
                                    <?php
                                    $activeUser = new User();
                                    /** @var \PortlandLabs\CommunityBadges\AwardService $awardService */
                                    $awardService = $app->make(\PortlandLabs\CommunityBadges\AwardService::class);
                                    $totalAwards = count($awardService->getAllGrantedAwardsByUser($activeUser));

                                    $grantedAwardList = [];

                                    foreach ($awardService->getAllGrantedAwardsGroupedByUser($activeUser) as $awardItem) {
                                        $grantedAward = $awardItem["grantedAward"];
                                        if ($grantedAward instanceof \PortlandLabs\CommunityBadges\Entity\AwardGrant) {
                                            $award = $grantedAward->getAward();

                                            if ($award instanceof \PortlandLabs\CommunityBadges\Entity\Award) {
                                                $grantedAwardList[$grantedAward->getId()] = $award->getName();
                                            }
                                        }
                                    }
                                    ?>

                                    <?php if (!$isOwnProfile && $totalAwards > 0) { ?>
                                        <a href="javascript:void(0);"
                                           data-toggle="modal" data-target="#giveAward"
                                           class="give-award btn award-icon btn-success<?php echo $totalAwards > 1 ? " badge-container" : ""; ?>">
                                            <?php echo t("Give Award"); ?>

                                            <?php if ($totalAwards > 1) { ?>
                                                <div class="badge-counter">
                                                    <?php echo $totalAwards; ?>
                                                </div>
                                            <?php } ?>
                                        </a>

                                        <div class="modal community-award-modal" tabindex="-1" role="dialog"
                                             id="giveAward">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <?php echo t("Give Award"); ?>
                                                        </h5>
                                                    </div>

                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <?php echo $form->label("grantedAwardId", t("Award")); ?>
                                                            <?php echo $form->select("grantedAwardId", $grantedAwardList); ?>
                                                        </div>

                                                        <div class="form-group">
                                                            <?php echo $form->hidden("user", $profile->getUserID()); ?>
                                                            <?php echo $form->label("userName", t("User")); ?>
                                                            <?php echo $form->text("userName", $profile->getUserName(), ["readonly" => "readonly"]); ?>
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">
                                                            <?php echo t("Cancel"); ?>
                                                        </button>

                                                        <button type="button" class="btn btn-primary">
                                                            <?php echo t("Give Award"); ?>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                <?php } ?>

                                <a href="<?php echo (string)Url::to("account/messages"); ?>" class="btn btn-secondary">
                                    <?php echo t("Inbox"); ?>
                                </a>

                                <?php if (!$isOwnProfile) { ?>
                                    <a href="javascript:void(0);" class="btn btn-primary send-message"
                                       data-receiver="<?php echo h($profile->getUserID()); ?>">
                                        <?php echo t("Send Message"); ?>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix"></div>

                    <div class="profile-description">
                        <?php if ($profile->getAttribute('website') != "") { ?>
                            <div class="profile-website">
                                <a href="<?php echo h((string)$profile->getAttribute('website')) ?>">
                                    <?php
                                    $urlParts = parse_url((string)$profile->getAttribute('website'));
                                    echo $urlParts["host"];
                                    ?>
                                </a>
                            </div>
                        <?php } ?>

                        <?php if (strlen($profile->getAttribute('description')) === 0) { ?>
                            <?php echo t("None entered."); ?>
                        <?php } else { ?>
                            <?php echo nl2br($profile->getAttribute('description')); ?>
                        <?php } ?>
                    </div>

                    <div class="profile-user-actions">
                        <a href="<?php echo (string)Url::to("account/messages"); ?>" class="btn btn-secondary">
                            <?php echo t("Inbox"); ?>
                        </a>

                        <?php if (!$isOwnProfile) { ?>
                            <a href="javascript:void(0);" class="btn btn-primary send-message"
                               data-receiver="<?php echo h($profile->getUserID()); ?>">
                                <?php echo t("Send Message"); ?>
                            </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="clearfix"></div>

                <?php if ($isCommunityAwardsModuleInstalled) { ?>
                    <?php
                    /** @var \PortlandLabs\CommunityBadges\AwardService $awardService */
                    $awardService = $app->make(\PortlandLabs\CommunityBadges\AwardService::class);
                    $communityBadgesPackageEntity = $packageService->getByHandle("community_Badges");
                    /** @var \Concrete\Core\Package\Package $communityBadgesPackage */
                    $communityBadgesPackage = $communityBadgesPackageEntity->getController();
                    ?>

                    <?php foreach ($awardService->getAllGrantedAwardsByUser($user) as $awardGrant) { ?>
                        <?php $award = $awardGrant->getAward(); ?>

                        <?php if ($award instanceof \PortlandLabs\CommunityBadges\Entity\Award && !$awardGrant->isDismissed()) { ?>
                            <div class="row">
                                <div class="col">
                                    <div class="alert alert-new-badge alert-dismissible fade show" role="alert"
                                         data-award-grant-id="<?php echo (int)$awardGrant->getId(); ?>">
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>

                                        <div class="alert-info-text">
                                            <div class="text-center">
                                                <?php echo t("You've been granted a new award to giveaway!"); ?>
                                            </div>
                                        </div>

                                        <div class="badge-icon">
                                            <div class="text-center">
                                                <?php
                                                $badgeUrl = $communityBadgesPackage->getRelativePath() . "/images/default_badge.png";

                                                $badgeThumbnail = $award->getThumbnail();

                                                if ($badgeThumbnail instanceof File) {
                                                    $badgeThumbnailVersion = $badgeThumbnail->getApprovedVersion();
                                                    if ($badgeThumbnailVersion instanceof Version) {
                                                        $badgeUrl = $badgeThumbnailVersion->getURL();
                                                    }
                                                }

                                                $imageElement = new Image($badgeUrl, $award->getName());
                                                echo $imageElement->render();
                                                ?>
                                            </div>
                                        </div>

                                        <div class="badge-name">
                                            <div class="text-center">
                                                <?php echo $award->getName(); ?>
                                            </div>
                                        </div>

                                        <div class="text-center">
                                            <a href="javascript:void(0);"
                                               data-toggle="modal"
                                               data-target="#giveAward-<?php echo $awardGrant->getId(); ?>"
                                               class="give-award btn award-icon btn-success">
                                                <?php echo t("Give Award"); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal community-award-modal" tabindex="-1" role="dialog"
                                 id="giveAward-<?php echo $awardGrant->getId(); ?>">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <?php echo t("Give Award"); ?>
                                            </h5>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-group">
                                                <?php echo $form->label("grantedAwardName", t("Award")); ?>
                                                <?php echo $form->text("grantedAwardName", $award->getName(), ["readonly" => "readonly"]); ?>
                                                <?php echo $form->hidden("grantedAwardId", $awardGrant->getId()); ?>
                                            </div>

                                            <div class="form-group">
                                                <?php if ($isOwnProfile) { ?>
                                                    <?php echo $form->label("user", t("User")); ?>
                                                    <?php echo $userSelector->quickSelect("user"); ?>
                                                <?php } else { ?>
                                                    <?php echo $form->hidden("user", $profile->getUserID()); ?>
                                                    <?php echo $form->label("userName", t("User")); ?>
                                                    <?php echo $form->text("userName", $profile->getUserName(), ["readonly" => "readonly"]); ?>
                                                <?php } ?>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">
                                                <?php echo t("Cancel"); ?>
                                            </button>

                                            <button type="button" class="btn btn-primary">
                                                <?php echo t("Give Award"); ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card" id="info-card">
                            <div class="card-header d-flex align-items-center">
                                <?php echo t("Information"); ?>
                                <?php if ($isOwnProfile) { ?>
                                    <a href="<?php echo (string)Url::to('/account/edit_profile') ?>"
                                       class="ml-auto btn btn-sm btn-secondary float-right">
                                        <?php echo t("Edit Profile"); ?>
                                    </a>
                                <?php } ?>
                            </div>
                            <div class="card-body">

                                <div class="card-text">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <?php $this->inc('elements/members/user_attribute.php', [
                                                "title" => t("Past Experience"),
                                                "attribute" => $profile->getAttribute('past_experience')
                                            ]); ?>
                                        </div>

                                        <div class="col-md-6">
                                            <?php $this->inc('elements/members/user_attribute.php', [
                                                "title" => t("Associations"),
                                                "attribute" => $profile->getAttribute('associations')
                                            ]); ?>
                                        </div>
                                    </div>

                                    <div class="row">

                                        <div class="col-md-6">
                                            <?php $this->inc('elements/members/user_attribute.php', [
                                                "title" => t("Contact"),
                                                "attribute" => $profile->getAttribute('address')
                                            ]); ?>
                                            <?php if ($profile->getAttribute('phone') != "") { ?>
                                                <a href="tel:<?php echo h((string)$profile->getAttribute('phone')) ?>">
                                                    <?php echo (string)$profile->getAttribute('phone') ?>
                                                </a>
                                            <?php } ?>
                                        </div>

                                        <div class="col-md-6">
                                            <?php $this->inc('elements/members/user_attribute.php', [
                                                "title" => t("Current Specialies"),
                                                "attribute" => $profile->getAttribute('current_specialties')
                                            ]); ?>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md">
                                            <?php $this->inc('elements/members/user_attribute.php', [
                                                "title" => t("Education"),
                                                "attribute" => $profile->getAttribute('education')
                                            ]); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>