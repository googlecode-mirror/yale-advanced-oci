-- phpMyAdmin SQL Dump
-- version 3.3.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 26, 2011 at 01:48 AM
-- Server version: 5.1.41
-- PHP Version: 5.3.2-1ubuntu4.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `yale_advanced_oci`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` varchar(4095) NOT NULL,
  `requirements` varchar(1023) NOT NULL,
  `exam_group` int(10) unsigned NOT NULL,
  `extra_info` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1841 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_areas`
--

CREATE TABLE IF NOT EXISTS `course_areas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `area` char(5) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_id` (`course_id`,`area`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1173 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_flags`
--

CREATE TABLE IF NOT EXISTS `course_flags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `flag` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_id` (`course_id`,`flag`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1677 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_names`
--

CREATE TABLE IF NOT EXISTS `course_names` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `subject` char(4) NOT NULL,
  `number` char(6) NOT NULL,
  `section` tinyint(3) unsigned NOT NULL,
  `oci_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject` (`subject`,`number`,`section`),
  KEY `course_id` (`course_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1841 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_professors`
--

CREATE TABLE IF NOT EXISTS `course_professors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `professor` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_id` (`course_id`,`professor`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2015 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_sessions`
--

CREATE TABLE IF NOT EXISTS `course_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday','HTBA') NOT NULL,
  `start_time` double NOT NULL,
  `end_time` double NOT NULL,
  `location` varchar(63) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_id` (`course_id`,`day_of_week`,`start_time`),
  KEY `class_id` (`course_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9825 ;

-- --------------------------------------------------------

--
-- Table structure for table `course_skills`
--

CREATE TABLE IF NOT EXISTS `course_skills` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `skill` char(5) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_id` (`course_id`,`skill`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=626 ;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_comments`
--

CREATE TABLE IF NOT EXISTS `evaluation_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `comment` varchar(4095) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_id_2` (`course_id`,`type`,`comment`(63)),
  KEY `course_id` (`course_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=27407 ;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_courses`
--

CREATE TABLE IF NOT EXISTS `evaluation_courses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `season` mediumint(8) unsigned NOT NULL,
  `rating_1` smallint(5) unsigned NOT NULL,
  `rating_2` smallint(5) unsigned NOT NULL,
  `rating_3` smallint(5) unsigned NOT NULL,
  `rating_4` smallint(5) unsigned NOT NULL,
  `rating_5` smallint(5) unsigned NOT NULL,
  `difficulty_1` smallint(5) unsigned NOT NULL,
  `difficulty_2` smallint(5) unsigned NOT NULL,
  `difficulty_3` smallint(5) unsigned NOT NULL,
  `difficulty_4` smallint(5) unsigned NOT NULL,
  `difficulty_5` smallint(5) unsigned NOT NULL,
  `major_0` smallint(5) unsigned NOT NULL,
  `major_1` smallint(5) unsigned NOT NULL,
  `requirements_0` smallint(5) unsigned NOT NULL,
  `requirements_1` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `season` (`season`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2158 ;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_course_names`
--

CREATE TABLE IF NOT EXISTS `evaluation_course_names` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `subject` char(4) NOT NULL,
  `number` char(6) NOT NULL,
  `section` tinyint(3) unsigned NOT NULL,
  `season` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject` (`subject`,`number`,`section`,`season`),
  KEY `course_id` (`course_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3241 ;

-- --------------------------------------------------------

--
-- Table structure for table `exam_groups`
--

CREATE TABLE IF NOT EXISTS `exam_groups` (
  `id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `time` double NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
