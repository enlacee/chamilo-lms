<?php

namespace ChamiloLMS\CourseBundle\Tool;

/**
 * Interface ToolInterface
 * @package ChamiloLMS\CourseBundle\Tool
 */
interface ToolInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getLink();

    /**
     * @return string
     */
    public function getTarget();

    /**
     * @return string
     */
    public function getCategory();

    /**
     * @return string
     */
    //public function getName();

}
