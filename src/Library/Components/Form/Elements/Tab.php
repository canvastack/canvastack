<?php

namespace Canvastack\Canvastack\Library\Components\Form\Elements;

/**
 * Created on 19 Mar 2021
 *
 * Time Created : 03:32:17
 *
 * @filesource Tab.php
 *
 * @author     wisnuwidi@canvastack.com - 2021
 * @copyright  wisnuwidi
 *
 * @email      wisnuwidi@canvastack.com
 */
trait Tab
{
    /**
     * --[openTabHTMLForm]--
     */
    private $opentabHTML = '--[openTabHTMLForm]--';

    /**
     * --[openNewTab]--
     */
    private $openNewTab = '--[openNewTab]--';

    /**
     * --[openNewTabClass]--
     */
    private $openNewTabClass = '--[openNewTabClass]--';

    /**
     * --[closeTabHTMLForm]--
     */
    private $closedtabHTML = '--[closeTabHTMLForm]--';

    /**
     * Create Open Tab
     *
     * @param  string  $label
     * @param  string  $class
     * @return string
     *
     * @author: wisnuwidi
     */
    public function openTab($label, $class = false)
    {
        $classAttribute = false;
        if ($class) {
            $classAttribute = "{$this->openNewTabClass}{$class}";
        }

        $this->draw("{$this->opentabHTML}{$label}{$classAttribute}{$this->openNewTab}");
    }

    private $contentTab = null;

    public function addTabContent($content)
    {
        $this->contentTab = $content;
        $this->draw("<div class=\"diy-add-tab-content\">{$this->contentTab}</div>");
    }

    /**
     * Create Close Tab
     * 		: After the Open Tab
     *
     * @author: wisnuwidi
     */
    public function closeTab()
    {
        $this->draw("{$this->closedtabHTML}");
    }

    /**
     * HTML Tab Builder
     *
     * @param  string  $object
     *
     * @author: wisnuwidi
     */
    public function renderTab($object)
    {
        if (true === is_array($object)) {
            $object = implode('', $object);
        }

        $openTab = false;
        $closeTab = false;
        $dataBeforeTab = false;
        $dataMiddleTab = false;
        $dataAfterTab = false;

        // check if isset open tab string pointer
        if (true === canvastack_string_contained($object, $this->opentabHTML)) {
            // find the close tab string
            $closeTab = explode($this->closedtabHTML, $object);

            // delete last array after closed tab
            $dataAfterTab = $closeTab[count($closeTab) - 1];
            unset($closeTab[count($closeTab) - 1]);

            $openTabs = [];
            foreach ($closeTab as $index => $newTab) {
                $openTab = explode($this->opentabHTML, $newTab);

                // prevent data with form tag open, contained in the string(s) data
                if (true === canvastack_string_contained($openTab[$index], '<form method=')) {
                    $dataBeforeTab = $openTab[$index];
                    unset($openTab[$index]);
                }

                $openTabs[] = $openTab;
            }

            $tabContainer = [];
            $tabHeaders = [];
            $tabContents = [];
            $tabContainerEnd = [];

            foreach ($openTabs as $list => $tabs) {
                $tabContainer[$list][] = '<div class="tabbable">';
                $tabHeaders[$list][] = '<ul class="nav nav-tabs" role="tablist">';
                $tabContents[$list][] = '<div class="tab-content">';

                foreach ($tabs as $index => $tab) {
                    if (isset($tab) && ! empty($tab)) {
                        $sliceTabs = explode($this->openNewTab, $tab);
                    }

                    if (true === canvastack_string_contained($tab, $this->openNewTab)) {
                        $activeHeader = false;
                        $activeContent = false;

                        // set active for 1st tab
                        if (1 === $index) {
                            $activeHeader = 'active';
                            $activeContent = 'in active';
                        }

                        // detect if isset label and class string in label block
                        $label = trim($sliceTabs[0]);
                        $labelClass = false;
                        if (canvastack_string_contained($sliceTabs[0], $this->openNewTabClass)) {
                            $sliceLabel = explode($this->openNewTabClass, $label);
                            $label = trim($sliceLabel[0]);
                            $labelClass = trim($sliceLabel[1]);
                        }

                        $tabHeaders[$list][] = canvastack_form_create_header_tab($label, strtolower(canvastack_clean_strings($label)), $activeHeader, $labelClass);
                        $tabContents[$list][] = canvastack_form_create_content_tab(trim($sliceTabs[1]), strtolower(canvastack_clean_strings($label)), $activeContent);
                    } else {
                        $tabHeaders[$list][] = $tab.'<hr />';
                    }
                }

                $tabContents[$list][] = '</div>';
                $tabHeaders[$list][] = '</ul>';
                $tabContainerEnd[$list][] = '</div><br />';
            }

            $tabsObject = [];
            foreach ($tabHeaders as $index => $header) {
                $tabsObject[] = array_merge($tabContainer[$index], $tabHeaders[$index], $tabContents[$index], $tabContainerEnd[$index]);
            }

            $tabObjects = '';
            foreach ($tabsObject as $tabLists) {
                foreach ($tabLists as $tabList) {
                    $tabObjects .= $tabList;
                }
            }

            // Set New Data String(s)
            return [$dataBeforeTab.$tabObjects.$dataMiddleTab.$dataAfterTab];
        }
    }
}
