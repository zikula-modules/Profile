<?php

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - http://zikula.org/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ProfileModule\Twig;

use DataUtil;
use DateUtil;
use ModUtil;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use System;
use Twig_Environment;
use UserUtil;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\Core\Event\GenericEvent;
use Zikula\ExtensionsModule\Api\VariableApi;
use Zikula\UsersModule\Constant as UsersConstant;

/**
 * Twig extension class.
 */
class TwigExtension extends \Twig_Extension
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Access to the request information.
     *
     * @var Request
     */
    protected $request;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var VariableApi
     */
    private $variableApi;

    /**
     * TwigExtension constructor.
     *
     * @param TranslatorInterface      $translator      TranslatorInterface service instance
     * @param RequestStack             $requestStack    RequestStack service instance
     * @param EventDispatcherInterface $eventDispatcher EventDispatcher service instance
     * @param Twig_Environment         $twig            Twig_Environment service instance
     * @param VariableApi              $variableApi     VariableApi service instance
     */
    public function __construct(TranslatorInterface $translator, RequestStack $requestStack, EventDispatcherInterface $eventDispatcher, Twig_Environment $twig, VariableApi $variableApi)
    {
        $this->translator = $translator;
        $this->request = $requestStack->getCurrentRequest();
        $this->eventDispatcher = $eventDispatcher;
        $this->twig = $twig;
        $this->variableApi = $variableApi;
    }

    /**
     * Returns a list of custom Twig functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('zikulaprofilemodule_gravatar', [$this, 'getGravatarImage'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('zikulaprofilemodule_displayProfileSection', [$this, 'displayProfileSection'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('dudItemDisplay', [$this, 'dudItemDisplay'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('dudItemModify', [$this, 'dudItemModify'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('zikulaprofilemodule_modUrlLegacy', [$this, 'modUrlLegacy']),
        ];
    }

    /**
     * The zikulaprofilemodule_gravatar function returns either a Gravatar URL
     * or a complete image tag for a specified email address.
     *
     * Example:
     *     {{ zikulaprofilemodule_gravatar(email = 'user@example.com') }}
     *
     * @see http://en.gravatar.com/site/implement/images/php/
     *
     * @param string $email The email address
     * @param int    $size  Size in pixels; defaults to 80px [ 1 - 2048 ]
     * @param string $d     Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
     * @param string $r     Maximum rating (inclusive) [ g | pg | r | x ]
     * @param bool   $f     Force default image; defaults to FALSE
     * @param bool   $image TRUE to return a complete IMG tag, FALSE for just the URL
     *
     * @return string Either just a URL or a complete image tag
     */
    public function getGravatarImage($email = 'user@example.com', $size = 80, $d = 'mm', $r = 'g', $f = false, $image = true)
    {
        $result = $this->request->isSecure() ? 'https://secure.gravatar.com/avatar/' : 'http://www.gravatar.com/avatar/';
        $result .= md5(strtolower(trim($email))).'.jpg';
        $result .= '?s='.$size.'&amp;d='.$d.'&amp;r='.$r.($f ? '&amp;f='.$f : '');

        if ($image) {
            $result = '<img src="'.$result.'" class="img-thumbnail" alt="'.$this->translator->__('Avatar').'" />';
        }

        return $result;
    }

    /**
     * The zikulaprofilemodule_displayProfileSection function displays a section of the user profile.
     *
     * Example
     *     {{ zikulaprofilemodule_displayProfileSection(name='News') }}
     *
     * @param int    $userId Identifier of the user for which this profile section should be displayed
     * @param string $name   Section name to render
     *
     * @return string The rendered section; empty string if the section is not defined or an error occured
     */
    public function displayProfileSection($userId = 0, $name = '')
    {
        if (empty($userId) || empty($name)) {
            return '';
        }

        $nameLowered = strtolower($name);

        // extract the items to list
        $section = ModUtil::apiFunc('ZikulaProfileModule', 'section', $nameLowered, [
            'uid'  => $userId,
            'name' => $nameLowered,
        ]);
        if (false === $section) {
            return '';
        }

        $template = 'Section/'.$nameLowered.'.html.twig';

        $output = '';

        try {
            $output = $this->twig->render('@ZikulaProfileModule/'.$template, [
                'section' => $section,
            ]);
        } catch (Exception $e) {
            // template does not exist
        }

        return $output;
    }

    /**
     * The dudItemDisplay function displays an editable dynamic user data field.
     *
     * Examples
     *     {{ dudItemDisplay(propAttribute='avatar') }}
     *     {{ dudItemDisplay(propAttribute='realname', userId=uid) }}
     *     {{ dudItemDisplay(item=item) }}
     *
     * @param string $item          The Profile DUD item
     * @param string $userInfo      The userinfo information [if not set userId must be specified]
     * @param string $userId        User ID to display the field value for (-1 = do not load)
     * @param string $propLabel     Property label to display (optional overrides the preformated dud item $item)
     * @param string $propAttribute Property attribute to display
     * @param string $default       Default content for an empty DUD
     * @param bool   $showLabel     Whether to show the label or not, default = true
     *
     * @return string The rendered field; empty string if an error occured
     */
    public function dudItemDisplay($item, $userInfo, $userId = 0, $propLabel = '', $propAttribute = '', $default = '', $showLabel = true)
    {
        if (!isset($item)) {
            if (isset($propLabel)) {
                $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['proplabel' => $propLabel]);
            } elseif (isset($propAttribute)) {
                $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propattribute' => $propAttribute]);
            } else {
                return false;
            }
        }

        if (!isset($item) || empty($item)) {
            return false;
        }

        // check for a template set
        if (!isset($tplset)) {
            $tplset = 'dudDisplay';
        }

        if (empty($userId)) {
            $userId = UserUtil::getVar('uid');
        }

        if (empty($userInfo)) {
            $userInfo = UserUtil::getVars($userId);
        }

        // get the value of this field from the userinfo array
        if (isset($userInfo['__ATTRIBUTES__'][$item['prop_attribute_name']])) {
            $userValue = $userInfo['__ATTRIBUTES__'][$item['prop_attribute_name']];
        } elseif (isset($userInfo[$item['prop_attribute_name']])) {
            // user's temp view for non-approved users needs this
            $userValue = $userInfo[$item['prop_attribute_name']];
        } else {
            // can be a non-marked checkbox in the user temp data
            $userValue = '';
        }

        // try to get the DUD output if it's Third Party
        if ($item['prop_dtype'] != 1) {
            $output = ModUtil::apiFunc($item['prop_modname'], 'dud', 'edit', [
                'item'      => $item,
                'userinfo'  => $$userInfo,
                'uservalue' => $userValue,
                'default'   => $default,
            ]);
            if ($output) {
                return $output;
            }
        }

        $templateParameters = [
            'userInfo'  => $userInfo,
            'userValue' => $userValue,
        ];

        $output = '';

        // checks the different attributes and types
        // avatar
        if ($item['prop_attribute_name'] == 'avatar') {
            $baseurl = System::getBaseUrl();
            $avatarPath = $this->variableApi->get(UsersConstant::MODNAME, UsersConstant::MODVAR_AVATAR_IMAGE_PATH, UsersConstant::DEFAULT_AVATAR_IMAGE_PATH);
            if (empty($userValue)) {
                $userValue = 'blank.png';
            }

            $output = '<img alt="" src="'.$baseurl.$avatarPath.'/'.$userValue.'" />';
        } elseif ($item['prop_attribute_name'] == 'tzoffset') {
            // timezone
            if (empty($userValue)) {
                $userValue = UserUtil::getVar('tzoffset') ? UserUtil::getVar('tzoffset') : $this->variableApi->get(VariableApi::CONFIG, 'timezone_offset');
            }

            $output = DateUtil::getTimezoneText($userValue);
            if (!$output) {
                return '';
            }
        } elseif ($item['prop_displaytype'] == 2) {
            // checkbox
            $item['prop_listoptions'] = (empty($item['prop_listoptions'])) ? '@@No@@Yes' : $item['prop_listoptions'];

            if (!empty($item['prop_listoptions'])) {
                $options = array_values(array_filter(explode('@@', $item['prop_listoptions'])));

                /*
                * Detect if the list options include the modification of the label.
                */
                if (substr($item['prop_listoptions'], 0, 2) != '@@') {
                    $item['prop_label'] = array_shift($options);
                }

                $userValue = isset($userValue) ? (bool) $userValue : 0;
                $output = $options[$userValue];
            } else {
                $output = $userValue;
            }
        } elseif ($item['prop_displaytype'] == 3) {
            // radio
            $options = $this->getDudFieldOptions($item);

            // process the user value and get the translated label
            $output = isset($options[$userValue]) ? $options[$userValue] : $default;
        } elseif ($item['prop_displaytype'] == 4) {
            // select
            $options = $this->getDudFieldOptions($item);

            $output = [];
            foreach ((array) $userValue as $id) {
                if (isset($options[$id])) {
                    $output[] = $options[$id];
                }
            }
        } elseif (!empty($userValue) && $item['prop_displaytype'] == 5) {
            // date
            $format = $this->getDudFieldOptions($item);
            switch (trim(strtolower($format))) {
                case 'us':
                    $dateformat = 'F j, Y';
                    break;
                case 'db':
                    $dateformat = 'Y-m-d';
                    break;
                default:
                case 'eur':
                    $dateformat = 'j F Y';
                    break;
            }
            $date = new DateTime($userValue);
            $output = $date->format($dateformat);
        } elseif ($item['prop_displaytype'] == 7) {
            // multicheckbox
            $options = $this->getDudFieldOptions($item);

            // process the user values and get the translated label
            $userValue = @unserialize($userValue);

            $output = [];
            foreach ((array) $userValue as $id) {
                if (isset($options[$id])) {
                    $output[] = $options[$id];
                }
            }
        } elseif ($item['prop_attribute_name'] == 'url') {
            // url
            if (!empty($userValue) && $userValue != 'http://') {
                //! string to describe the user's site
                $output = '<a href="'.DataUtil::formatForDisplay($userValue).'" title="'.$this->translator->__f("%s's site", ['%s' => $userInfo['uname']]).'" rel="nofollow">'.DataUtil::formatForDisplay($userValue).'</a>';
            }
        } elseif (empty($userValue)) {
            // process the generics
            $output = $default;
        } elseif (DataUtil::is_serialized($userValue) || is_array($userValue)) {
            // serialized data
            $userValue = !is_array($userValue) ? unserialize($userValue) : $userValue;
            $output = [];
            foreach ($userValue as $option) {
                $output[] = $option;
            }
        } else {
            // a string
            $output .= $userValue;
        }

        // omit this field if is empty after the process
        if (empty($output)) {
            return '';
        }

        $templateParameters['item'] = $item;
        $templateParameters['output'] = is_array($output) ? $output : [$output];

        /*
        TODO enable custom template sets again
        try {
            $template = $tplset . '_' . $item['prop_id'] . '.html.twig';

            return $this->twig->render('@ZikulaProfileModule/UsersUi/' . $template, $templateParameters);
        } catch (Exception $e) {*/
            // custom template does not exist
            $template = $tplset.'_generic.html.twig';

        return $this->twig->render('@ZikulaProfileModule/UsersUi/'.$template, $templateParameters);
        //}
    }

    /**
     * The dudItemModify function displays an editable dynamic user data field.
     *
     * Examples
     *     {{ dudItemModify(propAttribute='avatar') }}
     *     {{ dudItemModify(propAttribute='realname', userId=uid) }}
     *     {{ dudItemModify(item=item) }}
     *     {{ dudItemModify(item=item, fieldName=false) }}
     *
     * @param string      $item          The Profile DUD item
     * @param string      $userId        User ID to display the field value for (-1 = do not load)
     * @param string      $class         CSS class to assign to the table row/form row div (optional)
     * @param string      $propLabel     Property label to display (optional overrides the preformated dud item $item)
     * @param string      $propAttribute Property attribute to display
     * @param string      $error         Property error message
     * @param bool|string $fieldName     Name of the array of elements that comprise the fields; defaults to "dynadata";
     *                                   set to FALSE for fields without an array
     *
     * @return string The rendered field; empty string if an error occured
     */
    public function dudItemModify($item, $userId = 0, $class = '', $propLabel = '', $propAttribute = '', $error = '', $fieldName = 'dynadata')
    {
        if (empty($item)) {
            if (isset($propLabel)) {
                $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['proplabel' => $propLabel]);
            } elseif (isset($propAttribute)) {
                $item = ModUtil::apiFunc('ZikulaProfileModule', 'user', 'get', ['propattribute' => $propAttribute]);
            } else {
                return false;
            }
        }

        if (empty($item)) {
            return false;
        }

        // detect if we are in the registration form
        $onRegistrationForm = false;

        if ((strtolower($this->request->query->get('module')) == 'zikulausersmodule')
            && (strtolower($this->request->query->get('type')) == 'user')
            && (strtolower($this->request->query->get('func')) == 'register')) {
            $onRegistrationForm = true;
        }

        // skip the field if not configured to be on the registration form
        if ($onRegistrationForm && !$item['prop_required']) {
            $dudregshow = $this->variableApi->get('ZikulaProfileModule', 'dudregshow', []);

            if (!in_array($item['prop_attribute_name'], $dudregshow)) {
                return;
            }
        }

        if (empty($userId)) {
            $userId = UserUtil::getVar('uid');
        }
        if (!is_string($class)) {
            $class = '';
        }

        $user = UserUtil::getVars($userId);

        if (isset($item['temp_propdata'])) {
            $userValue = $item['temp_propdata'];
        } elseif ($userId >= 0) {
            $isRegistration = UserUtil::isRegistration($userId);
            $userValue = UserUtil::getVar($item['prop_attribute_name'], $userId, false, $isRegistration); // ($alias, $userId);
        }

        // try to get the DUD output if it's Third Party
        if ($item['prop_dtype'] != 1) {
            $output = ModUtil::apiFunc($item['prop_modname'], 'dud', 'edit', [
                'item'      => $item,
                'uservalue' => $userValue,
                'class'     => $class,
            ]);
            if ($output) {
                return $output;
            }
        }

        $fieldObject = isset($fieldName) ? (!$fieldName ? null : $fieldName) : 'dynadata';
        $fieldName = (isset($fieldName) ? (!$fieldName ? $item['prop_attribute_name'] : $fieldName.'['.$item['prop_attribute_name'].']') : 'dynadata['.$item['prop_attribute_name'].']');

        // assign the default values for the control
        $templateParameters = [
            'class'         => $class,
            'fieldName'     => $fieldName,
            'fieldObject'   => $fieldObject,
            'value'         => $userValue,
            'item'          => $item,
            'attributeName' => $item['prop_attribute_name'],
            'propLabelText' => $item['prop_label'],
            'note'          => $item['prop_note'],
            'required'      => (bool) $item['prop_required'],
            'error'         => $error,
        ];

        // Excluding Timezone of the generics
        if ($item['prop_attribute_name'] == 'tzoffset') {
            if (empty($userValue)) {
                $userValue = UserUtil::getVar('tzoffset') ? UserUtil::getVar('tzoffset') : $this->variableApi->get(VariableApi::CONFIG, 'timezone_offset');
            }

            $tzinfo = DateUtil::getTimezones();

            $templateParameters['selectedValue'] = isset($tzinfo[$userValue]) ? $userValue : null;
            $templateParameters['selectMultiple'] = '';
            $templateParameters['listOptions'] = $tzinfo;

            return $this->renderDudEditTemplate('select', $templateParameters);
        }

        if ($item['prop_attribute_name'] == 'avatar') {
            // only shows a link to the Avatar module if available
            if (ModUtil::available('Avatar')) {
                // TODO Add a change-link to the admins
                // only shows the link for the own user
                if (UserUtil::getVar('uid') != $userId) {
                    return '';
                }
                $templateParameters['linkText'] = $this->translator->__('Go to the Avatar manager');
                $templateParameters['linkUrl'] = ModUtil::url('Avatar', 'user', 'main');

                $output = $this->renderDudEditTemplate('link', $templateParameters);

                // add a hidden input if this is required
                if ($item['prop_required']) {
                    $output .= $this->renderDudEditTemplate('hidden', $templateParameters);
                }

                return $output;
            }

            // display the avatar selector
            if (empty($userValue)) {
                $userValue = 'gravatar.jpg';
            }
            $templateParameters['selectedValue'] = $userValue;

            $avatarPath = $this->variableApi->get(UsersConstant::MODNAME, UsersConstant::MODVAR_AVATAR_IMAGE_PATH, UsersConstant::DEFAULT_AVATAR_IMAGE_PATH);

            $finder = new Finder();
            $finder->files()->in($avatarPath)->name('/\.gif|\.jpg|\.png$/');
            $finder->sortByName();

            $listOptions = [];
            foreach ($finder as $file) {
                $listOptions[$file->getFilename()] = str_replace($file->getExtension(), '', $file->getFilename());
            }

            $templateParameters['selectMultiple'] = '';
            $templateParameters['listOptions'] = $listOptions;

            return $this->renderDudEditTemplate('select', $templateParameters);
        }

        switch ($item['prop_displaytype']) {
            case 0:
                $type = 'text';
                break;

            case 1:
                $type = 'textarea';
                break;

            case 2:
                $type = 'checkbox';

                $options = ['No', 'Yes'];
                if (!empty($item['prop_listoptions'])) {
                    $options = array_values(array_filter(explode('@@', $item['prop_listoptions'])));

                    /*
                    * Detect if the list options include the modification of the label.
                    */
                    if (substr($item['prop_listoptions'], 0, 2) != '@@') {
                        $templateParameters['propLabelText'] = array_shift($options);
                    }
                }
                break;

            case 3:
                $type = 'radio';

                $templateParameters['listOptions'] = $this->getDudFieldOptions($item);
                break;

            case 4:
                $type = 'select';
                if (DataUtil::is_serialized($userValue)) {
                    $templateParameters['selectedValue'] = unserialize($userValue);
                }

                // multiple flag is the first field
                $options = explode('@@', $item['prop_listoptions'], 2);
                $templateParameters['selectMultiple'] = $options[0] ? ' multiple="multiple"' : '';

                $options = [];
                if ($item['prop_attribute_name'] == 'country' || substr($item['prop_attribute_name'], -8) == '_country') {
                    $countries = \ZLanguage::countryMap();
                    asort($countries);
                    $options = $countries;
                } else {
                    $options = $this->getDudFieldOptions($item);
                }

                $event_subject = $user;
                $event_args = [
                    'item' => $item,
                ];
                $event_data = $options;

                $event = new GenericEvent($event_subject, $event_args, $event_data);
                $event = $this->eventDispatcher->dispatch('module.profile.dud_item_modify', $event);

                if ($event->isPropagationStopped()) {
                    $options = $event->getData();
                }

                $templateParameters['listOptions'] = $options;
                break;

            case 5:
                $type = 'date';

                // gets the format to use
                $format = $this->getDudFieldOptions($item);

                switch (trim(strtolower($format))) {
                    case 'us':
                        $formats = [
                            'dt' => 'F j, Y',
                            'js' => 'MM d, yy',
                        ];
                        break;
                    case 'db':
                        $formats = [
                            'dt' => 'Y-m-d',
                            'js' => 'yy-mm-dd',
                        ];
                        break;
                    default:
                    case 'eur':
                        $formats = [
                            'dt' => 'j F Y',
                            'js' => 'd MM yy',
                        ];
                        break;
                }

                // process the temporal data if any
                $timestamp = (!empty($userValue)) ? time() : null;
                if (isset($item['temp_propdata'])) {
                    $timestamp = DateUtil::parseUIDate($item['temp_propdata']);
                    $userValue = DateUtil::transformInternalDate($timestamp);
                } elseif (!empty($userValue)) {
                    $timestamp = DateUtil::makeTimestamp($userValue);
                }

                $templateParameters['valueDateTime'] = !empty($userValue) ? new DateTime($userValue) : null;
                $templateParameters['timestamp'] = $timestamp;
                $templateParameters['formats'] = $formats;
                break;

            case 7:
                $type = 'multicheckbox';

                $templateParameters['selectedValue'] = (array) unserialize($userValue);
                $templateParameters['fields'] = $this->getDudFieldOptions($item);
                break;

            default:
                $type = 'text';
                break;
        }

        return $this->renderDudEditTemplate($type, $templateParameters);
    }

    /**
     * Returns options for a given DUD field.
     *
     * @param string $item The Profile DUD item
     *
     * @return array Options for the DUD item
     */
    private function getDudFieldOptions($item)
    {
        return ModUtil::apiFunc('ZikulaProfileModule', 'dud', 'getoptions', ['item' => $item]);
    }

    /**
     * Renders a DUD editing template.
     *
     * @param string $templateName Template file name without extension
     * @param array  $parameters   Template variables array
     *
     * @return string Absolute path to template
     */
    private function renderDudEditTemplate($templateName, $parameters = [])
    {
        return $this->twig->render('@ZikulaProfileModule/DudEdit/'.$templateName.'.html.twig', $parameters);
    }

    /**
     * Legacy bridging method for arbitrary module urls.
     *
     * @param string $moduleName Name of target module
     * @param string $type       Name of target type
     * @param string $func       Name of target function
     * @param array  $args       Additional arguments
     *
     * @return string
     */
    public function modUrlLegacy($moduleName, $type = 'user', $func = 'index', $args = [])
    {
        return ModUtil::url($moduleName, 'user', 'index');
    }
}
