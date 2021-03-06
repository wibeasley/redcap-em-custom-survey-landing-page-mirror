<?php
/**
 * Created by PhpStorm.
 * User: andy123
 * Date: 5/16/18
 * Time: 2:00 PM
 */

namespace Stanford\CustomSurveyLandingPage;

use REDCap;

class CustomSurveyLandingPage extends \ExternalModules\AbstractExternalModule
{

    public function getImageUrl() {
        $img_doc_id = $this->getProjectSetting('image');
        $img_path = \Files::copyEdocToTemp($img_doc_id);
        return $img_path;
    }

    public function getImage64() {
        $path = $this->getImageUrl();
        $contents = file_get_contents($path);
        $mime = mime_content_type($path);
        return "data:" . $mime . ";base64," . base64_encode($contents);
    }


    /**
     * Add some context to the config page to help the user-interface
     * @param null $project_id
     * @return bool
     */
    function xredcap_module_configure_button_display($project_id = null) {
        $publicUrl = empty($this->getPublicUrl()) ? "''" : json_encode($this->getPublicUrl());
        $shortUrl = empty($this->getShortUrl()) ? "''" : json_encode($this->getShortUrl());

	?>
            <script type="text/javascript">
		var CSLP = CSLP || {};
                CSLP.surveyUrl = <?php echo $publicUrl ?>;
                CSLP.surveyShortUrl = <?php echo $shortUrl ?>;
            </script>
            <style>
                code.selectOnClick { cursor: pointer; }
            </style>
        <?php
        return true;
    }


    /**
     * Generate a url to the custom survey entry page
     * @return string
     */
    private function getPublicUrl() {
        global $auth_meth;
        $is_above_843 = REDCap::versionCompare(REDCAP_VERSION, '8.4.3') >= 0;
        $url = ($auth_meth === "shibboleth" && $is_above_843)  ? $this->getUrl("survey.php", true, true) : $this->getUrl("survey.php");
        return $url;
    }

    private static function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    private function getShortUrl() {
        $shortUrl = $this->getProjectSetting('short-url');
        if (empty($shortUrl)) {
            // Try to make one
            $publicUrl = $this->getPublicUrl();
            $publicUrl = str_replace("localhost","redcap.stanford.edu",$publicUrl);
            $result = \Survey::getCustomShortUrl($publicUrl, false);

            if (self::startsWith($result, "Error:")) {
                $shortUrl = false;
            } else {
                $shortUrl = $result;
                $this->setProjectSetting('short-url', $shortUrl);
            }
        }
        return $shortUrl;
    }

    function redcap_every_page_top($project_id = null) {

        if (PAGE === "Surveys/invite_participants.php" || PAGE === "DataEntry/index.php") {

            $shortUrl = $this->getShortUrl();
            ?>
                <script>
                    // Override the getAccessCode with an anonymous function
                    (function () {
                        // Cache the original function under another name
                        var proxied = getAccessCode;

                        // Redefine the original
                        getAccessCode = function () {

                            // Do the original proxied function
                            $result = proxied.apply(this, arguments);

                            // Add some custom JS to update the Access Code page with the alternate URLs
                            waitForUrl('textarea.staticInput', function() {
                                // work the magic
                                var shortUrl = <?php echo json_encode($this->getShortUrl()) ?>;
                                var publicUrl = <?php echo json_encode($this->getPublicUrl()) ?>;

                                // Get the default url:
                                var ta = $('textarea.staticInput').addClass('smallUrl');

                                // Add the custom urls:
                                var newta = ta.clone().text('<?php echo $this->getPublicUrl() ?>');
                                ta.after(newta).after($('<p class="">-- OR, for your Custom Landing Page, use this long url:</p>'));

                                if (shortUrl) {
                                    newta = ta.clone().text(shortUrl);
                                    ta.after(newta).after($('<p class="">-- OR, for your Custom Landing Page, you may use this shortened url:</p>'));
                                }
                            });

                            return $result;
                        }
                    })();


                    // Wait for the url to appear on the Survey Access Code page
                    waitForUrl = function(selector, callback) {
                        if (jQuery(selector).text().length) {
                            callback();
                        } else {
                            setTimeout(function() {
                                waitForUrl(selector, callback);
                            }, 100);
                        }
                    };

                </script>
                <style>
                    textarea.smallUrl {
                        font-size: smaller !important;
                        width: 95% !important;
                        height: 26px !important;
                        overflow-x:scroll;
                        white-space: nowrap !important;
                    }
                </style>
            <?php
        }
    }

}
