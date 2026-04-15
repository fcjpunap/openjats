-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 09, 2026 at 07:04 AM
-- Server version: 11.8.6-MariaDB-0+deb13u1 from Debian
-- PHP Version: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jats_assistant`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `article_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `affiliations`
--

CREATE TABLE `affiliations` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `affiliation_id` varchar(50) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `issue_id` int(11) DEFAULT NULL,
  `article_id` varchar(50) NOT NULL,
  `doi` varchar(100) DEFAULT NULL,
  `title` text NOT NULL,
  `title_en` text DEFAULT NULL,
  `abstract` text DEFAULT NULL,
  `abstract_en` text DEFAULT NULL,
  `keywords` text DEFAULT NULL,
  `keywords_en` text DEFAULT NULL,
  `article_type` varchar(255) DEFAULT NULL,
  `language` varchar(10) DEFAULT 'es',
  `received_date` date DEFAULT NULL,
  `accepted_date` date DEFAULT NULL,
  `published_date` date DEFAULT NULL,
  `pagination` varchar(50) DEFAULT NULL,
  `status` enum('draft','processing','marked','published') DEFAULT 'draft',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `template_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `articles`
--

INSERT INTO `articles` (`id`, `issue_id`, `article_id`, `doi`, `title`, `title_en`, `abstract`, `abstract_en`, `keywords`, `keywords_en`, `article_type`, `language`, `received_date`, `accepted_date`, `published_date`, `pagination`, `status`, `uploaded_by`, `created_at`, `updated_at`, `template_id`) VALUES
(7, NULL, '20260322_986f3d', '', 'La motivación tecnológica en la fiscalización tributaria automatizada y su función garantista en la protección del derecho de defensa en la región andina', '', 'El presente\nartículo analiza el impacto de la fiscalización tributaria automatizada en la\nconfiguración contemporánea del deber de motivación administrativa y examina la\nnecesidad de un estándar de motivación tecnológica que garantice la protección\ndel derecho de defensa en la región andina. El estudio adopta un enfoque\njurídico-dogmático de carácter analítico, sustentado en la revisión crítica de\nla doctrina administrativa, la teoría del debido proceso y los desarrollos\nrecientes sobre decisiones tecnológicamente mediadas. Se identifica que los\nestándares tradicionales de motivación, diseñados para decisiones\npredominantemente humanas, resultan insuficientes cuando los actos\nadministrativos se sustentan en cruces automatizados de datos, generando\nriesgos de opacidad decisional y asimetrías informativas. Se propone\nconceptualizar la motivación tecnológica como una exigencia cualificada de\njustificación que incorpore criterios mínimos de explicabilidad, trazabilidad y\nacceso a la información relevante, permitiendo compatibilizar la eficiencia del\ncontrol tributario con las garantías procedimentales y fortalecer la\nlegitimidad de la actuación administrativa en entornos digitales.\n\n  ', 'The article examines the impact of automated tax auditing on the contemporary configuration of the administrative duty to provide reasons and analyzes the need for a technological motivation standard capable of safeguarding the right of defense in the Andean region. The study adopts a doctrinal and analytical legal approach, grounded in a critical review of administrative law scholarship, due process theory, and recent debates on technology-mediated decision-making. It argues that traditional standards of administrative reasoning, originally designed for predominantly human decisions, are insufficient when tax determinations rely on automated data cross-checking, generating risks of decisional opacity and structural informational asymmetries. The paper conceptualizes technological motivation as a qualified justification requirement that incorporates minimum standards of explainability, traceability, and access to relevant information. Such an approach enables the reconciliation of enforcement efficiency with procedural guarantees and strengthens the legitimacy of tax administration in digitally mediated environments. ', 'Fiscalización tributaria automatizada, motivación tecnológica, debido proceso, derecho de defensa, crucesautomatizados de datos, región andina. ', 'Automated tax auditing, technological motivation, due process, right of defense, automated data cross-checking, Andean region. I.     IntroducciónLa acelerada digitalización de lasadministraciones tributarias ha redefinido los mecanismos tradicionales defiscalización, incorporando herramientas tecnológicas capaces de procesargrandes volúmenes de información y detectar inconsistencias fiscales conniveles de precisión inéditos. El uso de cruces automatizados de datos, modelospredictivos y sistemas de análisis masivo ha fortalecido la capacidad decontrol estatal y hacontribuido a mejorar la eficiencia recaudatoria, consolidándose como un rasgocaracterístico de los sistemas tributarios contemporáneos (OECD, 2020).No obstante, este proceso de modernizacióntecnológica también plantea desafíos relevantes para el derecho administrativotributario, particularmente en lo que respecta a las garantías que estructuranel debido proceso. Entre ellas, el deber de motivación de los actosadministrativos adquiere una importancia renovada.  La motivación constituye unelemento estructural del acto administrativo en el Estado de derecho (García deEnterría & Fernández, 2017), en la medida en que es el principal mecanismoa través del cual se hace visible el razonamiento de la autoridad y seposibilita el ejercicio efectivo del derecho de defensa del contribuyente.Tradicionalmente, la motivación ha sidoentendida como la obligación de la administración de expresar de manera claralos fundamentos fácticos y jurídicos que sustentan sus decisiones. Este deberno solo permite controlar la arbitrariedad del poder público, sino que tambiénopera como condición de legitimidad de la actuación administrativa. Sinembargo, los parámetros clásicos desde los cuales se ha construido estagarantía responden a un modelo decisional predominantemente humano, en el quela reconstrucción lógica del acto resulta accesible a partir de la exposiciónargumentativa del funcionario.La progresiva automatización de losprocesos de fiscalización introduce, en este escenario, un elemento decomplejidad adicional. Cuando la detección de riesgos fiscales se apoya ensistemas tecnológicos cuya lógica operativa no es plenamente transparente parael administrado, la motivación corre el riesgo de transformarse en unaexplicación meramente formal, incapaz de revelar los criterios sustantivos quecondujeron a la decisión administrativa. Esta situación puede generarescenarios de opacidad incompatibles con los estándares garantistas propios delEstado constitucional, en la medida en que las garantías procedimentalescumplen la función de racionalizar el ejercicio del poder público comoexigencia estructural del constitucionalismo contemporáneo (Ferrajoli, 2011).El problema adquiere especial relevancia enel contexto de la región andina, donde las administraciones tributarias hanavanzado de manera sostenida en la incorporación de herramientas digitales comoparte de sus estrategias de control y determinación. Si bien estos procesosrepresentan una oportunidad para fortalecer la gestión fiscal, también obligana repensar las categorías jurídicas tradicionales con el fin de evitar que lainnovación tecnológica derive en restricciones indirectas al ejercicio de los derechosde los contribuyentes.En este contexto, surge una cuestióncentral para el derecho tributario contemporáneo: ¿resultan suficientes losestándares tradicionales de motivación para garantizar el derecho de defensacuando las decisiones tributarias se sustentan en procesos automatizados deanálisis de datos? Interrogar esta premisa implica reconocer que latransformación tecnológica no solo modifica las formas de actuaciónadministrativa, sino que también tensiona los marcos conceptuales desde loscuales se han protegido históricamente las garantías procedimentales.El presente estudio se desarrolla a partirde un enfoque jurídico-dogmático de carácter analítico, basado en la revisióncrítica de la doctrina del derecho administrativo, la teoría del debido procesoy los aportes contemporáneos sobre decisiones tecnológicamente mediadas. Através de este método, se examinan las tensiones que surgen entre lafiscalización tributaria automatizada y las garantías procedimentales clásicas, con el propósito de formular una propuesta conceptual que contribuya alfortalecimiento de la motivación administrativa en entornos digitales, particularmente en el contexto de la región andina.El artículo tiene como objetivo analizar elimpacto de la fiscalización tributaria automatizada en la configuración actualdel deber de motivación administrativa y examinar la necesidad de reconocer lamotivación tecnológica como un estándar orientado a reforzar la protección delderecho de defensa. Se sostiene que la insuficiencia motivacional en entornosdigitalizados puede limitar materialmente la capacidad de contradicción delcontribuyente y debilitar el control jurídico de la actuación administrativa.Como principal aporte, el trabajo proponecomprender la motivación tecnológica no solo como una adaptación instrumentaldel deber de motivar, sino como una exigencia cualificada de justificación quepermita compatibilizar la eficiencia del control fiscal con el respetoirrestricto de las garantías procedimentales. Desde esta perspectiva, latransparencia decisional se presenta como una condición indispensable parapreservar la legitimidad del poder tributario en escenarios de crecienteautomatización.Para el desarrollo de esta propuesta, elartículo se estructura en cuatro apartados. En primer lugar, se examina laevolución del deber de motivación en el derecho administrativo y su funcióncomo garantía frente al ejercicio del poder público. En segundo término, seanalizan los desafíos que la fiscalización automatizada plantea para losestándares tradicionales de fundamentación. Posteriormente, se formula lanoción de motivación tecnológica como categoría conceptual necesaria para elderecho administrativo tributario contemporáneo. En el cuarto apartado, seabordan sus implicaciones para el derecho de defensa y el controljurisdiccional, incorporando además una reflexión crítica sobre los límites ydesafíos de implementación de este estándar en la región andina. Finalmente, sesintetizan y se formulan las conclusiones del estudio.II. DESARROLLO', 'research-article', 'es', '2026-02-23', NULL, '2026-03-17', '', 'processing', 1, '2026-03-22 23:21:13', '2026-03-25 23:08:43', NULL),
(9, 1, '20260323_613683', 'https://doi.org/10.47712/rd.2026.v11i1.359', 'La motivación tecnológica en la fiscalización tributaria automatizada y su función garantista en la protección del derecho de defensa en la región andina', 'Technological Motivation in Automated Tax Auditing and Its Guarantee Function in the Protection of the Right of Defense in the Andean Region', 'El presente artículo analiza el impacto de la fiscalización tributaria automatizada en la configuración contemporánea del deber de motivación administrativa y examina la necesidad de un estándar de motivación tecnológica que garantice la protección del derecho de defensa en la región andina. El estudio adopta un enfoque jurídico-dogmático de carácter analítico, sustentado en la revisión crítica de la doctrina administrativa, la teoría del debido proceso y los desarrollos recientes sobre decisiones tecnológicamente mediadas. Se identifica que los estándares tradicionales de motivación, diseñados para decisiones predominantemente humanas, resultan insuficientes cuando los actos administrativos se sustentan en cruces automatizados de datos, generando riesgos de opacidad decisional y asimetrías informativas. Se propone conceptualizar la motivación tecnológica como una exigencia cualificada de justificación que incorpore criterios mínimos de explicabilidad, trazabilidad y acceso a la información relevante, permitiendo compatibilizar la eficiencia del control tributario con las garantías procedimentales y fortalecer la legitimidad de la actuación administrativa en entornos digitales.', 'The article examines the impact of automated tax auditing on the contemporary configuration of the administrative duty to provide reasons and analyzes the need for a technological motivation standard capable of safeguarding the right of defense in the Andean region. The study adopts a doctrinal and analytical legal approach, grounded in a critical review of administrative law scholarship, due process theory, and recent debates on technology-mediated decision-making. It argues that traditional standards of administrative reasoning, originally designed for predominantly human decisions, are insufficient when tax determinations rely on automated data cross-checking, generating risks of decisional opacity and structural informational asymmetries. The paper conceptualizes technological motivation as a qualified justification requirement that incorporates minimum standards of explainability, traceability, and access to relevant information. Such an approach enables the reconciliation of enforcement efficiency with procedural guarantees and strengthens the legitimacy of tax administration in digitally mediated environments.', 'Fiscalización tributaria automatizada, motivación tecnológica, debido proceso, derecho de defensa, cruces automatizados de datos, región andina', 'Automated tax auditing, technological motivation, due process, right of defense, automated data cross-checking, Andean region', 'TEORÍA CRÍTICA, FILOSOFÍA Y METODOLOGÍA DEL DERECHO', 'es', '2026-02-23', '2026-03-11', '2026-03-17', 'e202609', 'processing', 1, '2026-03-23 00:50:14', '2026-04-08 11:28:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `article_figures`
--

CREATE TABLE `article_figures` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `figure_id` varchar(50) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `mime_type` varchar(50) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `figure_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `article_files`
--

CREATE TABLE `article_files` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `file_type` enum('source_zip','html','xml_jats','pdf','epub') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(50) DEFAULT NULL,
  `version` int(11) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `article_files`
--

INSERT INTO `article_files` (`id`, `article_id`, `file_type`, `file_path`, `file_size`, `mime_type`, `version`, `created_at`) VALUES
(18, 7, 'source_zip', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/7/original.zip', 17472, 'application/zip', 1, '2026-03-22 23:21:13'),
(19, 7, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/7/source/article.html', NULL, 'text/html', 1, '2026-03-22 23:21:13'),
(23, 9, 'source_zip', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/9/original.zip', 17472, 'application/zip', 1, '2026-03-23 00:50:14'),
(24, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/9/source/article.html', NULL, 'text/html', 1, '2026-03-23 00:50:14'),
(25, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 30747, 'text/html', 1, '2026-03-23 00:52:40'),
(26, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 21552, 'application/pdf', 1, '2026-03-23 01:28:29'),
(27, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 34795, 'text/html', 1, '2026-03-23 01:32:01'),
(28, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 379532, 'application/pdf', 1, '2026-03-23 02:31:06'),
(29, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 36640, 'text/html', 1, '2026-03-23 02:38:24'),
(30, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 384233, 'application/pdf', 1, '2026-03-23 04:17:13'),
(31, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 39389, 'text/html', 1, '2026-03-23 04:18:05'),
(32, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 39389, 'text/html', 1, '2026-03-23 04:32:21'),
(33, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 49830, 'text/html', 1, '2026-03-23 04:46:01'),
(34, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 393836, 'application/pdf', 1, '2026-03-23 04:50:49'),
(35, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 401361, 'application/pdf', 1, '2026-03-23 05:34:00'),
(36, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 58881, 'text/html', 1, '2026-03-23 05:38:52'),
(37, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 401388, 'application/pdf', 1, '2026-03-23 05:41:12'),
(38, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 401388, 'application/pdf', 1, '2026-03-23 05:49:10'),
(39, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 403640, 'application/pdf', 1, '2026-03-23 07:43:13'),
(40, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 65334, 'text/html', 1, '2026-03-23 07:44:38'),
(41, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 54452, 'application/xml', 1, '2026-03-24 13:21:00'),
(42, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 403640, 'application/pdf', 1, '2026-03-24 13:21:22'),
(43, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 65334, 'text/html', 1, '2026-03-24 13:21:47'),
(44, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 402917, 'application/pdf', 1, '2026-03-24 13:42:36'),
(45, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 65505, 'text/html', 1, '2026-03-24 13:44:09'),
(46, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 404463, 'application/pdf', 1, '2026-03-24 14:11:45'),
(47, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 404463, 'application/pdf', 1, '2026-03-24 14:14:58'),
(48, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 404426, 'application/pdf', 1, '2026-03-24 14:25:55'),
(49, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 54452, 'application/xml', 1, '2026-03-24 14:30:48'),
(50, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 404486, 'application/pdf', 1, '2026-03-24 14:35:15'),
(51, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 404304, 'application/pdf', 1, '2026-03-24 14:50:38'),
(52, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 405518, 'application/pdf', 1, '2026-03-24 14:59:26'),
(53, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 405514, 'application/pdf', 1, '2026-03-24 15:00:00'),
(54, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 54453, 'application/xml', 1, '2026-03-24 15:42:23'),
(55, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 404231, 'application/pdf', 1, '2026-03-24 15:42:32'),
(56, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 54482, 'application/xml', 1, '2026-03-24 15:46:45'),
(57, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 53000, 'application/xml', 1, '2026-03-24 15:47:19'),
(58, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 65507, 'text/html', 1, '2026-03-24 15:47:30'),
(59, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 54482, 'application/xml', 1, '2026-03-24 15:50:17'),
(60, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 53000, 'application/xml', 1, '2026-03-24 15:50:33'),
(61, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 404231, 'application/pdf', 1, '2026-03-24 20:22:44'),
(62, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 402527, 'application/pdf', 1, '2026-03-24 20:31:20'),
(63, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 402812, 'application/pdf', 1, '2026-03-24 20:37:34'),
(64, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 403771, 'application/pdf', 1, '2026-03-24 20:42:35'),
(65, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 403771, 'application/pdf', 1, '2026-03-24 20:47:15'),
(66, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 405331, 'application/pdf', 1, '2026-03-24 21:08:21'),
(67, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 54482, 'application/xml', 1, '2026-03-24 21:09:52'),
(68, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 402619, 'application/pdf', 1, '2026-03-24 21:16:36'),
(69, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 402619, 'application/pdf', 1, '2026-03-24 21:28:20'),
(70, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 402767, 'application/pdf', 1, '2026-03-24 21:46:23'),
(71, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 403071, 'application/pdf', 1, '2026-03-24 21:51:03'),
(72, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 54482, 'application/xml', 1, '2026-03-24 21:54:03'),
(73, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 56716, 'application/xml', 1, '2026-03-24 22:01:05'),
(74, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 53000, 'application/xml', 1, '2026-03-24 22:06:37'),
(75, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 56716, 'application/xml', 1, '2026-03-24 22:09:52'),
(76, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 53000, 'application/xml', 1, '2026-03-24 22:12:07'),
(77, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 56702, 'application/xml', 1, '2026-03-24 22:25:44'),
(78, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 57102, 'application/xml', 1, '2026-03-24 22:28:28'),
(79, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 57146, 'application/xml', 1, '2026-03-24 22:30:39'),
(80, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 55247, 'application/xml', 1, '2026-03-24 22:32:07'),
(81, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 56226, 'application/xml', 1, '2026-03-24 22:35:39'),
(82, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 403071, 'application/pdf', 1, '2026-03-24 22:37:44'),
(83, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 65507, 'text/html', 1, '2026-03-24 22:38:16'),
(84, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 61625, 'application/xml', 1, '2026-03-25 03:40:50'),
(85, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 64318, 'application/xml', 1, '2026-03-25 03:40:57'),
(86, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 63263, 'application/xml', 1, '2026-03-25 03:41:04'),
(87, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 410380, 'application/pdf', 1, '2026-03-25 03:41:13'),
(88, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 73015, 'text/html', 1, '2026-03-25 03:43:51'),
(89, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 73914, 'text/html', 1, '2026-03-25 04:37:42'),
(90, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 62626, 'application/xml', 1, '2026-03-25 04:47:32'),
(91, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 65319, 'application/xml', 1, '2026-03-25 04:49:11'),
(92, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 64264, 'application/xml', 1, '2026-03-25 04:51:28'),
(93, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 73914, 'text/html', 1, '2026-03-25 04:53:22'),
(94, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 73915, 'text/html', 1, '2026-03-25 04:54:26'),
(95, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 410309, 'application/pdf', 1, '2026-03-25 04:55:07'),
(96, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 65504, 'application/xml', 1, '2026-03-25 04:55:38'),
(97, 7, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/index.html', 42822, 'text/html', 1, '2026-03-25 16:50:30'),
(98, 7, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/index.html', 43761, 'text/html', 1, '2026-03-25 17:40:35'),
(99, 7, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/article.pdf', 388415, 'application/pdf', 1, '2026-03-25 17:41:48'),
(100, 7, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/index.html', 43761, 'text/html', 1, '2026-03-25 17:43:14'),
(101, 7, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/article.pdf', 387007, 'application/pdf', 1, '2026-03-25 17:45:39'),
(102, 7, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/article.pdf', 387007, 'application/pdf', 1, '2026-03-25 17:46:35'),
(103, 7, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/index.html', 41893, 'text/html', 1, '2026-03-25 17:46:47'),
(104, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 410309, 'application/pdf', 1, '2026-03-25 17:47:49'),
(105, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 73915, 'text/html', 1, '2026-03-25 17:52:04'),
(106, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 410309, 'application/pdf', 1, '2026-03-25 17:55:12'),
(107, 7, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/index.html', 41893, 'text/html', 1, '2026-03-25 18:04:38'),
(108, 7, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/index.html', 41491, 'text/html', 1, '2026-03-25 18:07:58'),
(109, 7, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260322_986f3d/index.html', 41228, 'text/html', 1, '2026-03-25 18:08:47'),
(110, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412276, 'application/pdf', 1, '2026-03-25 18:54:18'),
(111, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 75692, 'text/html', 1, '2026-03-25 18:54:35'),
(112, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412249, 'application/pdf', 1, '2026-03-25 19:15:54'),
(113, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 75692, 'text/html', 1, '2026-03-25 19:20:08'),
(114, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412249, 'application/pdf', 1, '2026-03-25 19:33:13'),
(115, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412330, 'application/pdf', 1, '2026-03-25 20:16:11'),
(116, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 75943, 'text/html', 1, '2026-03-25 20:17:06'),
(117, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412330, 'application/pdf', 1, '2026-03-25 20:34:24'),
(118, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 75936, 'text/html', 1, '2026-03-25 20:34:50'),
(119, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412312, 'application/pdf', 1, '2026-03-25 20:49:22'),
(120, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76079, 'text/html', 1, '2026-03-25 20:49:48'),
(121, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412329, 'application/pdf', 1, '2026-03-26 05:26:56'),
(122, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76079, 'text/html', 1, '2026-03-26 05:27:27'),
(123, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412283, 'application/pdf', 1, '2026-03-26 05:33:41'),
(124, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76079, 'text/html', 1, '2026-03-26 05:34:16'),
(125, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412283, 'application/pdf', 1, '2026-03-26 05:47:39'),
(126, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76079, 'text/html', 1, '2026-03-26 05:50:58'),
(127, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76079, 'text/html', 1, '2026-03-26 06:00:58'),
(128, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412283, 'application/pdf', 1, '2026-03-26 06:02:14'),
(129, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412283, 'application/pdf', 1, '2026-03-27 04:21:16'),
(130, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76079, 'text/html', 1, '2026-03-27 04:21:35'),
(131, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412283, 'application/pdf', 1, '2026-03-27 04:48:16'),
(132, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 75870, 'text/html', 1, '2026-03-27 04:50:44'),
(133, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 412283, 'application/pdf', 1, '2026-03-27 21:25:23'),
(134, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502882, 'application/pdf', 1, '2026-03-27 22:09:46'),
(135, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502841, 'application/pdf', 1, '2026-03-27 22:10:53'),
(136, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76669, 'text/html', 1, '2026-03-27 22:18:15'),
(137, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502815, 'application/pdf', 1, '2026-03-27 22:24:50'),
(138, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76958, 'text/html', 1, '2026-03-27 22:25:08'),
(139, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502815, 'application/pdf', 1, '2026-03-28 04:12:23'),
(140, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502818, 'application/pdf', 1, '2026-03-28 04:18:51'),
(141, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502832, 'application/pdf', 1, '2026-03-28 04:24:07'),
(142, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502832, 'application/pdf', 1, '2026-03-28 04:32:19'),
(143, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502832, 'application/pdf', 1, '2026-03-28 04:41:27'),
(144, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502832, 'application/pdf', 1, '2026-03-28 04:45:13'),
(145, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76958, 'text/html', 1, '2026-03-28 04:45:55'),
(146, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502832, 'application/pdf', 1, '2026-03-28 04:56:51'),
(147, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502906, 'application/pdf', 1, '2026-03-28 05:00:54'),
(148, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502832, 'application/pdf', 1, '2026-03-28 05:07:34'),
(149, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502832, 'application/pdf', 1, '2026-03-28 05:12:03'),
(150, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 408212, 'application/pdf', 1, '2026-03-28 05:21:25'),
(151, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502832, 'application/pdf', 1, '2026-03-28 05:26:21'),
(152, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 502832, 'application/pdf', 1, '2026-03-28 05:49:04'),
(153, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487883, 'application/pdf', 1, '2026-03-28 05:51:47'),
(154, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76958, 'text/html', 1, '2026-04-08 02:03:19'),
(155, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 02:24:15'),
(156, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 24354, 'application/xml', 1, '2026-04-08 02:24:22'),
(157, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 23503, 'application/xml', 1, '2026-04-08 02:24:30'),
(158, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487883, 'application/pdf', 1, '2026-04-08 02:24:41'),
(159, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487883, 'application/pdf', 1, '2026-04-08 02:27:31'),
(160, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76958, 'text/html', 1, '2026-04-08 02:27:57'),
(161, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487764, 'application/pdf', 1, '2026-04-08 02:28:16'),
(162, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487812, 'application/pdf', 1, '2026-04-08 02:29:27'),
(163, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76877, 'text/html', 1, '2026-04-08 02:29:50'),
(164, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 02:36:25'),
(165, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 24354, 'application/xml', 1, '2026-04-08 02:36:25'),
(166, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 23503, 'application/xml', 1, '2026-04-08 02:36:25'),
(167, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76877, 'text/html', 1, '2026-04-08 02:36:25'),
(168, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487812, 'application/pdf', 1, '2026-04-08 02:36:29'),
(169, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76877, 'text/html', 1, '2026-04-08 02:37:40'),
(170, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487812, 'application/pdf', 1, '2026-04-08 02:37:57'),
(171, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 02:41:26'),
(172, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 24354, 'application/xml', 1, '2026-04-08 02:41:26'),
(173, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 23503, 'application/xml', 1, '2026-04-08 02:41:26'),
(174, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76877, 'text/html', 1, '2026-04-08 02:41:26'),
(175, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487812, 'application/pdf', 1, '2026-04-08 02:41:30'),
(176, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 02:47:45'),
(177, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/scielo.xml', 24354, 'application/xml', 1, '2026-04-08 02:47:45'),
(178, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/redalyc.xml', 23503, 'application/xml', 1, '2026-04-08 02:47:45'),
(179, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76877, 'text/html', 1, '2026-04-08 02:47:45'),
(180, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487812, 'application/pdf', 1, '2026-04-08 02:47:49'),
(181, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 03:06:32'),
(182, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/scielo.xml', 24354, 'application/xml', 1, '2026-04-08 03:06:32'),
(183, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/redalyc.xml', 23503, 'application/xml', 1, '2026-04-08 03:06:32'),
(184, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-08 03:06:32'),
(185, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487764, 'application/pdf', 1, '2026-04-08 03:06:36'),
(186, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-08 03:08:03'),
(187, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 03:19:28'),
(188, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/scielo.xml', 24354, 'application/xml', 1, '2026-04-08 03:19:28'),
(189, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/redalyc.xml', 23503, 'application/xml', 1, '2026-04-08 03:19:28'),
(190, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-08 03:19:28'),
(191, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487764, 'application/pdf', 1, '2026-04-08 03:19:31'),
(192, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 03:27:24'),
(193, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/scielo.xml', 24354, 'application/xml', 1, '2026-04-08 03:27:24'),
(194, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/redalyc.xml', 23503, 'application/xml', 1, '2026-04-08 03:27:24'),
(195, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-08 03:27:24'),
(196, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487764, 'application/pdf', 1, '2026-04-08 03:27:27'),
(197, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 06:22:29'),
(198, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/scielo.xml', 24354, 'application/xml', 1, '2026-04-08 06:22:29'),
(199, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/redalyc.xml', 23503, 'application/xml', 1, '2026-04-08 06:22:29'),
(200, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-08 06:22:29'),
(201, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487725, 'application/pdf', 1, '2026-04-08 06:22:32'),
(202, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487725, 'application/pdf', 1, '2026-04-08 06:25:16'),
(203, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487725, 'application/pdf', 1, '2026-04-08 06:28:47'),
(204, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-08 06:29:13'),
(205, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 17:02:43'),
(206, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487725, 'application/pdf', 1, '2026-04-08 17:02:55'),
(207, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-08 17:10:19'),
(208, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.xml', 21660, 'application/xml', 1, '2026-04-08 17:15:13'),
(209, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/scielo.xml', 24354, 'application/xml', 1, '2026-04-08 17:15:13'),
(210, 9, 'xml_jats', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/redalyc.xml', 23503, 'application/xml', 1, '2026-04-08 17:15:13'),
(211, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-08 17:15:13'),
(212, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487725, 'application/pdf', 1, '2026-04-08 17:15:17'),
(213, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487725, 'application/pdf', 1, '2026-04-08 18:52:59'),
(214, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-08 18:55:06'),
(215, 9, 'pdf', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/article.pdf', 487725, 'application/pdf', 1, '2026-04-09 07:01:47'),
(216, 9, 'html', '/mnt/www_data/www/html/fcjp.derecho.unap.edu.pe/public_html/catg/jats-assistant/config/../public/articles/20260323_613683/index.html', 76870, 'text/html', 1, '2026-04-09 07:02:04');

-- --------------------------------------------------------

--
-- Table structure for table `article_footnotes`
--

CREATE TABLE `article_footnotes` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `fn_id` varchar(50) NOT NULL,
  `text` text DEFAULT NULL,
  `fn_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `article_footnotes`
--

INSERT INTO `article_footnotes` (`id`, `article_id`, `fn_id`, `text`, `fn_order`) VALUES
(409, 9, '1', 'La comprensión de la motivación como condición material de validez implica que su ausencia o insuficiencia no constituye un mero defecto formal subsanable, sino un vicio que puede afectar la legitimidad misma del acto administrativo.', 1),
(410, 9, '2', 'La exigencia de fundamentación se vincula con la idea de que toda decisión jurídica en un Estado constitucional debe ser racionalmente justificable, de modo que el ejercicio del poder público pueda ser sometido a escrutinio crítico.', 2),
(411, 9, '3', 'La nulidad en estos supuestos no deriva de un simple incumplimiento formal, sino de la afectación sustancial del derecho de defensa, lo que convierte la motivación en un presupuesto material de legitimidad del acto administrativo.', 3),
(412, 9, '4', 'Estas asimetrías no solo son de carácter técnico, sino también cognitivo, pues el contribuyente puede carecer de acceso a los parámetros algorítmicos o criterios internos utilizados en los procesos automatizados de decisión.', 4),
(413, 9, '5', 'La noción de technological due process propone precisamente que, cuando la decisión administrativa se apoya en sistemas automatizados, el estándar de justificación debe adaptarse a los riesgos específicos que introduce la mediación tecnológica en la formación del acto.', 5),
(414, 9, '6', 'La idea de “caja negra” alude a sistemas cuya lógica interna no es accesible o inteligible para los destinatarios de la decisión, lo que genera un déficit estructural de transparencia y dificulta el control racional del ejercicio del poder público.', 6),
(415, 9, '7', 'La noción de “algoritmos responsables” parte de la idea de que los sistemas automatizados deben ser susceptibles de revisión y supervisión externa, aun cuando su diseño técnico no sea completamente divulgado, garantizando así mecanismos mínimos de control jurídico.', 7),
(416, 9, '8', 'La integración de estándares jurídicos en el diseño y funcionamiento de tecnologías decisionales constituye una condición estructural para evitar que la innovación tecnológica opere al margen de los principios del Estado de derecho.', 8);

-- --------------------------------------------------------

--
-- Table structure for table `article_markup`
--

CREATE TABLE `article_markup` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `markup_data` longtext DEFAULT NULL,
  `xml_preview` longtext DEFAULT NULL,
  `last_saved` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `saved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `article_markup`
--

INSERT INTO `article_markup` (`id`, `article_id`, `markup_data`, `xml_preview`, `last_saved`, `saved_by`) VALUES
(1, 7, '{\"tables\":[{\"number\":1,\"label\":\"Tabla 1\",\"caption\":\"Priemera tabla\",\"html\":\"<table><tr><td>Contenido<\\/td><\\/tr><\\/table>\"}],\"images\":[]}', NULL, '2026-03-25 17:40:30', 1),
(35, 9, '{\"tables\":[{\"number\":1,\"label\":\"Tabla 1\",\"title\":\"Primera tabla\",\"caption\":\"Primera tabla\",\"html\":\"<table style=\\\"border-collapse: collapse; width: 100%; border: medium; margin-bottom: 1.5em;\\\"><tbody><tr style=\\\"border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);\\\"><th style=\\\"padding: 8px; border: medium; text-align: left;\\\">&nbsp;1<\\/th><th style=\\\"padding: 8px; border: medium; text-align: left;\\\">2&nbsp;<\\/th><th style=\\\"padding: 8px; border: medium; text-align: left;\\\">&nbsp;3<\\/th><\\/tr><tr><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;1<\\/td><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;2<\\/td><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;3<\\/td><\\/tr><tr style=\\\"border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);\\\"><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;1<\\/td><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;2<\\/td><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;3<\\/td><\\/tr><\\/tbody><\\/table>\",\"content\":\"<table style=\\\"border-collapse: collapse; width: 100%; border: medium; margin-bottom: 1.5em;\\\"><tbody><tr style=\\\"border-top-width: 2px; border-top-style: solid; border-top-color: rgb(0, 0, 0); border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);\\\"><th style=\\\"padding: 8px; border: medium; text-align: left;\\\">&nbsp;1<\\/th><th style=\\\"padding: 8px; border: medium; text-align: left;\\\">2&nbsp;<\\/th><th style=\\\"padding: 8px; border: medium; text-align: left;\\\">&nbsp;3<\\/th><\\/tr><tr><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;1<\\/td><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;2<\\/td><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;3<\\/td><\\/tr><tr style=\\\"border-bottom-width: 2px; border-bottom-style: solid; border-bottom-color: rgb(0, 0, 0);\\\"><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;1<\\/td><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;2<\\/td><td style=\\\"padding: 8px; border: medium;\\\">&nbsp;3<\\/td><\\/tr><\\/tbody><\\/table>\",\"nota\":\"Es una prueba de tabla\",\"type\":\"html\",\"src\":\"\",\"id\":\"table-1774469726611-0.6512447364145593\"}],\"images\":[{\"number\":1,\"label\":\"Figura 1\",\"caption\":\"Primera figura\",\"alt\":\"Primera figura\",\"src\":\"articles\\/9\\/asset_69c6fe608f4c9.png\",\"nota\":\"Es una prueba de imagen.\",\"width\":\"80%\",\"id\":\"fig-1774649368395\"}]}', NULL, '2026-04-08 06:28:40', 1);

-- --------------------------------------------------------

--
-- Table structure for table `article_references`
--

CREATE TABLE `article_references` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `ref_id` varchar(50) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `authors` text DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `volume` varchar(20) DEFAULT NULL,
  `issue` varchar(20) DEFAULT NULL,
  `pages` varchar(50) DEFAULT NULL,
  `doi` varchar(100) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `reference_order` int(11) NOT NULL,
  `full_citation` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `article_references`
--

INSERT INTO `article_references` (`id`, `article_id`, `ref_id`, `reference_type`, `authors`, `year`, `title`, `source`, `volume`, `issue`, `pages`, `doi`, `url`, `publisher`, `reference_order`, `full_citation`) VALUES
(1021, 9, 'ref-69d5f59835228', 'journal', 'Alexy, R.', '2002', 'Teoría de los derechos fundamentales', 'Centro de Estudios Políticos y Constitucionales.', NULL, NULL, '', '', '', NULL, 1, 'Alexy, R.. (2002). Teoría de los derechos fundamentales. Centro de Estudios Políticos y Constitucionales.'),
(1022, 9, 'ref-69d5f59835415', 'journal', 'Barocas, S., & Selbst, A. (2016). Big Data’s disparate impact. California Law Review, 104(3), 671–732.', '', '', '', NULL, NULL, '', '', '', NULL, 2, 'Barocas, S., & Selbst, A. (2016). Big Data’s disparate impact. California Law Review, 104(3), 671–732.'),
(1023, 9, 'ref-69d5f59835602', 'journal', 'Brownsword, R. (2019). Law, technology and society: Re-imagining the regulatory environment. Routledge.', '', '', '', NULL, NULL, '', '', '', NULL, 3, 'Brownsword, R. (2019). Law, technology and society: Re-imagining the regulatory environment. Routledge.'),
(1024, 9, 'ref-69d5f598357e8', 'journal', 'Citron, D. K. (2008). Technological due process. Washington University Law Review, 85(6), 1249–1313.', '', '', '', NULL, NULL, '', '', '', NULL, 4, 'Citron, D. K. (2008). Technological due process. Washington University Law Review, 85(6), 1249–1313.'),
(1025, 9, 'ref-69d5f598359c8', 'journal', 'Ferrajoli, L. (2011). Principia iuris: Teoría del derecho y de la democracia. Trotta.', '', '', '', NULL, NULL, '', '', '', NULL, 5, 'Ferrajoli, L. (2011). Principia iuris: Teoría del derecho y de la democracia. Trotta.'),
(1026, 9, 'ref-69d5f59835ba8', 'journal', 'García de Enterría, E., & Fernández, T. R. (2017). Curso de derecho administrativo (Vol. I). Civitas.', '', '', '', NULL, NULL, '', '', '', NULL, 6, 'García de Enterría, E., & Fernández, T. R. (2017). Curso de derecho administrativo (Vol. I). Civitas.'),
(1027, 9, 'ref-69d5f59835d88', 'journal', 'Hildebrandt, M. (2015). Smart technologies and the end(s) of law. Edward Elgar Publishing.', '', '', '', NULL, NULL, '', '', '', NULL, 7, 'Hildebrandt, M. (2015). Smart technologies and the end(s) of law. Edward Elgar Publishing.'),
(1028, 9, 'ref-69d5f59835f63', 'journal', 'Kroll, J. A., Huey, J., Barocas, S., Felten, E., Reidenberg, J., Robinson, D., & Yu, H. (2017). Accountable algorithms. University of Pennsylvania Law Review, 165(3), 633–705.', '', '', '', NULL, NULL, '', '', '', NULL, 8, 'Kroll, J. A., Huey, J., Barocas, S., Felten, E., Reidenberg, J., Robinson, D., & Yu, H. (2017). Accountable algorithms. University of Pennsylvania Law Review, 165(3), 633–705.'),
(1029, 9, 'ref-69d5f59836144', 'journal', 'OECD. (2020). Tax Administration 3.0: The digital transformation of tax administration. OECD Publishing.', '', '', '', NULL, NULL, '', '', '', NULL, 9, 'OECD. (2020). Tax Administration 3.0: The digital transformation of tax administration. OECD Publishing.'),
(1030, 9, 'ref-69d5f59836320', 'journal', 'Pasquale, F. (2015). The black box society: The secret algorithms that control money and information. Harvard University Press.', '', '', '', NULL, NULL, '', '', '', NULL, 10, 'Pasquale, F. (2015). The black box society: The secret algorithms that control money and information. Harvard University Press.'),
(1031, 9, 'ref-69d5f598364fd', 'journal', 'Santamaría Pastor, J. A. (2018). Principios de derecho administrativo general. Iustel.', '', '', '', NULL, NULL, '', '', '', NULL, 11, 'Santamaría Pastor, J. A. (2018). Principios de derecho administrativo general. Iustel.'),
(1032, 9, 'ref-69d5f598366df', 'journal', 'Wachter, S., Mittelstadt, B., & Floridi, L. (2017). Why a right to explanation of automated decision-making does not exist in the General Data Protection Regulation. International Data Privacy Law, 7(2), 76–99.', '', '', '', NULL, NULL, '', '', '', NULL, 12, 'Wachter, S., Mittelstadt, B., & Floridi, L. (2017). Why a right to explanation of automated decision-making does not exist in the General Data Protection Regulation. International Data Privacy Law, 7(2), 76–99.');

-- --------------------------------------------------------

--
-- Table structure for table `article_sections`
--

CREATE TABLE `article_sections` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `section_id` varchar(50) NOT NULL,
  `section_type` varchar(50) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `section_order` int(11) NOT NULL,
  `level` int(11) DEFAULT 1,
  `parent_section_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `article_sections`
--

INSERT INTO `article_sections` (`id`, `article_id`, `section_id`, `section_type`, `title`, `content`, `section_order`, `level`, `parent_section_id`) VALUES
(250, 7, 'sec-69c424ab2500a', NULL, 'La motivación tecnológica reforzada como', '\n\nLa acelerada digitalización de las\nadministraciones tributarias ha redefinido los mecanismos tradicionales de\nfiscalización, incorporando herramientas tecnológicas capaces de procesar\ngrandes volúmenes de información y detectar inconsistencias fiscales con\nniveles de precisión inéditos. El uso de cruces automatizados de datos, modelos\npredictivos y sistemas de análisis masivo ha fortalecido la capacidad de\ncontrol estatal y ha\ncontribuido a mejorar la eficiencia recaudatoria, consolidándose como un rasgo\ncaracterístico de los sistemas tributarios contemporáneos (OECD, 2020).\n\n\n\nNo obstante, este proceso de modernización\ntecnológica también plantea desafíos relevantes para el derecho administrativo\ntributario, particularmente en lo que respecta a las garantías que estructuran\nel debido proceso. Entre ellas, el deber de motivación de los actos\nadministrativos adquiere una importancia renovada.&nbsp; La motivación constituye un\nelemento estructural del acto administrativo en el Estado de derecho (García de\nEnterría &amp; Fernández, 2017), en la medida en que es el principal mecanismo\na través del cual se hace visible el razonamiento de la autoridad y se\nposibilita el ejercicio efectivo del derecho de defensa del contribuyente.\n\n\n\nTradicionalmente, la motivación ha sido\nentendida como la obligación de la administración de expresar de manera clara\nlos fundamentos fácticos y jurídicos que sustentan sus decisiones. Este deber\nno solo permite controlar la arbitrariedad del poder público, sino que también\nopera como condición de legitimidad de la actuación administrativa. Sin\nembargo, los parámetros clásicos desde los cuales se ha construido esta\ngarantía responden a un modelo decisional predominantemente humano, en el que\nla reconstrucción lógica del acto resulta accesible a partir de la exposición\nargumentativa del funcionario.\n\n\n\nLa progresiva automatización de los\nprocesos de fiscalización introduce, en este escenario, un elemento de\ncomplejidad adicional. Cuando la detección de riesgos fiscales se apoya en\nsistemas tecnológicos cuya lógica operativa no es plenamente transparente para\nel administrado, la motivación corre el riesgo de transformarse en una\nexplicación meramente formal, incapaz de revelar los criterios sustantivos que\ncondujeron a la decisión administrativa. Esta situación puede generar\nescenarios de opacidad incompatibles con los estándares garantistas propios del\nEstado constitucional, en la medida en que las garantías procedimentales\ncumplen la función de racionalizar el ejercicio del poder público como\nexigencia estructural del constitucionalismo contemporáneo (Ferrajoli, 2011).\n\n\n\nEl problema adquiere especial relevancia en\nel contexto de la región andina, donde las administraciones tributarias han\navanzado de manera sostenida en la incorporación de herramientas digitales como\nparte de sus estrategias de control y determinación. Si bien estos procesos\nrepresentan una oportunidad para fortalecer la gestión fiscal, también obligan\na repensar las categorías jurídicas tradicionales con el fin de evitar que la\ninnovación tecnológica derive en restricciones indirectas al ejercicio de los derechos\nde los contribuyentes.\n\n\n\nEn este contexto, surge una cuestión\ncentral para el derecho tributario contemporáneo: ¿resultan suficientes los\nestándares tradicionales de motivación para garantizar el derecho de defensa\ncuando las decisiones tributarias se sustentan en procesos automatizados de\nanálisis de datos? Interrogar esta premisa implica reconocer que la\ntransformación tecnológica no solo modifica las formas de actuación\nadministrativa, sino que también tensiona los marcos conceptuales desde los\ncuales se han protegido históricamente las garantías procedimentales.\n\n\n\nEl presente estudio se desarrolla a partir\nde un enfoque jurídico-dogmático de carácter analítico, basado en la revisión\ncrítica de la doctrina del derecho administrativo, la teoría del debido proceso\ny los aportes contemporáneos sobre decisiones tecnológicamente mediadas. A\ntravés de este método, se examinan las tensiones que surgen entre la\nfiscalización tributaria automatizada y las garantías procedimentales clásicas,\ncon el propósito de formular una propuesta conceptual que contribuya al\nfortalecimiento de la motivación administrativa en entornos digitales,\nparticularmente en el contexto de la región andina.\n\n\n\nEl artículo tiene como objetivo analizar el\nimpacto de la fiscalización tributaria automatizada en la configuración actual\ndel deber de motivación administrativa y examinar la necesidad de reconocer la\nmotivación tecnológica como un estándar orientado a reforzar la protección del\nderecho de defensa. Se sostiene que la insuficiencia motivacional en entornos\ndigitalizados puede limitar materialmente la capacidad de contradicción del\ncontribuyente y debilitar el control jurídico de la actuación administrativa.\n\n\n\nComo principal aporte, el trabajo propone\ncomprender la motivación tecnológica no solo como una adaptación instrumental\ndel deber de motivar, sino como una exigencia cualificada de justificación que\npermita compatibilizar la eficiencia del control fiscal con el respeto\nirrestricto de las garantías procedimentales. Desde esta perspectiva, la\ntransparencia decisional se presenta como una condición indispensable para\npreservar la legitimidad del poder tributario en escenarios de creciente\nautomatización.\n\n\n\nPara el desarrollo de esta propuesta, el\nartículo se estructura en cuatro apartados. En primer lugar, se examina la\nevolución del deber de motivación en el derecho administrativo y su función\ncomo garantía frente al ejercicio del poder público. En segundo término, se\nanalizan los desafíos que la fiscalización automatizada plantea para los\nestándares tradicionales de fundamentación. Posteriormente, se formula la\nnoción de motivación tecnológica como categoría conceptual necesaria para el\nderecho administrativo tributario contemporáneo. En el cuarto apartado, se\nabordan sus implicaciones para el derecho de defensa y el control\njurisdiccional, incorporando además una reflexión crítica sobre los límites y\ndesafíos de implementación de este estándar en la región andina. Finalmente, se\nsintetizan y se formulan las conclusiones del estudio.\n\n\n\nII.&nbsp;\nDESARROLLO\n\n\n\n\n\nEl\ndeber de motivación de los actos administrativos constituye uno de los pilares\nestructurales del Estado constitucional de derecho, en tanto opera como un\nmecanismo de racionalización del poder público y como una garantía frente a\neventuales actuaciones arbitrarias de la administración. En materia tributaria,\nesta exigencia adquiere una relevancia particular debido a la posición de\nsupremacía jurídica que ejerce la administración frente al contribuyente y al\nimpacto directo que sus decisiones pueden generar sobre la esfera patrimonial\nde los administrados.\n\n\n\nLa motivación\nno debe entenderse únicamente como una formalidad destinada a justificar la\ndecisión adoptada, sino como una condición material de validez del acto\nadministrativo (Santamaría Pastor, 2018).[1]\nSu función principal consiste en exteriorizar el razonamiento que conecta los\nhechos verificados con la norma aplicada, permitiendo así comprender el iter\nlógico-jurídico seguido por la autoridad. De este modo, la motivación cumple\nuna triple función: legitima el ejercicio de la potestad administrativa,\nposibilita el ejercicio efectivo del derecho de defensa y facilita el control\njurisdiccional posterior (Alexy, 2002).[2]\n\n\n\nDesde una\nperspectiva garantista, la ausencia o insuficiencia de motivación no solo\nconstituye un defecto estructural del acto, sino que puede generar su nulidad\npor vulneración del debido proceso (García de Enterría &amp; Fernández, 2017).[3] En\nefecto, cuando el contribuyente desconoce las razones concretas que sustentan\nuna determinación tributaria, se ve materialmente limitado para cuestionar la\ndecisión, ofrecer prueba pertinente o controvertir los criterios utilizados por\nla administración. La motivación, por tanto, no se agota en explicar el “qué”\nde la decisión, sino que debe revelar el “por qué” y el “cómo” de la misma.\n\n\n\nLa doctrina\nadministrativa contemporánea coincide en señalar que la motivación adecuada\nexige algo más que la mera cita de disposiciones normativas o la reproducción\nde fórmulas estandarizadas. Supone, por el contrario, una justificación\nindividualizada que atienda a las particularidades del caso concreto y que\nevidencie una verdadera actividad valorativa por parte de la autoridad. Este\nestándar resulta especialmente relevante en el ámbito tributario, donde la\ncomplejidad técnica de las determinaciones puede generar asimetrías\ninformativas significativas entre la administración y el contribuyente\n(Hildebrandt, 2015).[4]\n\n\n\nA ello se suma\nque la motivación cumple una función preventiva frente a la arbitrariedad. La\nobligación de explicar las razones de la decisión impone a la administración un\nejercicio de autocontrol que reduce el margen de discrecionalidad y favorece la\nadopción de decisiones fundadas en criterios jurídicamente verificables. En\neste sentido, la motivación actúa como un puente entre el principio de\nlegalidad y el derecho a la tutela administrativa efectiva.\n\n\n\nNo obstante,\nlos estándares tradicionales de motivación han sido construidos sobre la base\nde un modelo decisional predominantemente humano, en el que la autoridad\nadministrativa analiza los hechos, interpreta la norma y formula una conclusión\nque puede ser reconstruida a partir del texto del acto. Este paradigma comienza\na tensionarse en contextos donde la determinación tributaria se apoya en\nherramientas tecnológicas capaces de procesar grandes volúmenes de información\ny generar alertas o perfiles de riesgo mediante operaciones algorítmicas.\n\n\n\nLa cuestión que\nemerge, entonces, no es la vigencia del deber de motivación —cuya centralidad\npermanece incuestionable—, sino la suficiencia de sus parámetros clásicos\nfrente a nuevas formas de producción de la decisión administrativa. Si la\nmotivación tiene como finalidad hacer comprensible el razonamiento decisorio,\nresulta legítimo preguntarse hasta qué punto dicha finalidad puede cumplirse\ncuando parte de ese razonamiento se encuentra mediado por sistemas tecnológicos\ncuya lógica no siempre es accesible para el administrado.\n\n\n\nEste escenario\nobliga a replantear el alcance del deber de motivación en clave evolutiva. La\ngarantía no puede permanecer estática frente a transformaciones profundas en la\nforma en que la administración construye sus decisiones. Por el contrario, debe\nadaptarse para seguir cumpliendo su función protectora, evitando que la\nincorporación de tecnología derive en espacios de opacidad incompatibles con un\nmodelo garantista de actuación administrativa.\n\n\n\n\n\nLa\ntransformación digital de las administraciones tributarias ha introducido\nnuevas herramientas de control orientadas a optimizar la detección de\nincumplimientos fiscales. Entre ellas, los cruces automatizados de datos se han\nconsolidado como un instrumento estratégico para identificar inconsistencias\nentre la información declarada por los contribuyentes y aquella proveniente de\nterceros, registros financieros o bases de datos estatales. Este modelo de\nfiscalización preventiva permite ampliar la capacidad de supervisión de la\nadministración y mejorar los niveles de eficiencia recaudatoria.\n\n\n\nSin embargo, la\ncreciente dependencia de estos mecanismos tecnológicos también plantea desafíos\nrelevantes desde la perspectiva del derecho administrativo y tributario. Uno de\nlos más significativos es el riesgo de opacidad decisional,\nentendido como la dificultad del administrado para comprender los elementos\ndeterminantes que condujeron a la emisión del acto administrativo cuando estos\nse encuentran asociados a procesos automatizados de tratamiento de información.\n\n\n\nEn muchos\ncasos, la motivación de los actos derivados de cruces de datos se limita a\nseñalar la existencia de diferencias o inconsistencias detectadas por los\nsistemas informáticos, sin detallar aspectos esenciales como la fuente\nespecífica de los datos utilizados, los criterios de selección aplicados, la\nrelevancia atribuida a determinadas variables o el margen de error inherente al\nprocesamiento automatizado. Esta forma de motivación, aunque formalmente\nexistente, puede resultar materialmente insuficiente para garantizar una\ncontradicción efectiva (Citron, 2008).[5]\n\n\n\nEl problema no\nradica en el uso de tecnología —cuya incorporación resulta inevitable en\nadministraciones tributarias modernas—, sino en la posibilidad de que dicha\ntecnología opere como una “caja negra” que dificulte la reconstrucción del\nrazonamiento decisorio (Pasquale, 2015).[6]\nCuando el contribuyente no puede identificar con claridad el origen de la\ninconsistencia atribuida ni los parámetros utilizados para detectarla, su\ncapacidad de defensa se ve sustancialmente restringida.\n\n\n\nA ello se añade\nuna asimetría estructural de información. Mientras la administración dispone de\nacceso pleno a los sistemas y a la lógica de procesamiento, el contribuyente\nsolo conoce el resultado final de la operación tecnológica. Esta brecha\ninformativa puede traducirse en un desequilibrio procesal incompatible con los\nestándares contemporáneos del debido proceso administrativo.\n\n\n\nDesde una\nperspectiva garantista, la automatización no debería implicar una reducción de\nlas exigencias de motivación, sino, por el contrario, un reforzamiento de las\nmismas. Cuanto mayor sea la complejidad técnica del proceso decisional, mayor\ndebe ser el esfuerzo de la administración por hacerlo comprensible y\nverificable. La transparencia tecnológica se convierte así en una extensión\nnatural del deber de motivación.\n\n\n\nEste\nplanteamiento no supone exigir la revelación íntegra de algoritmos o sistemas\ncuya divulgación pudiera comprometer funciones de control fiscal. Implica, más\nbien, garantizar un nivel mínimo de explicabilidad que permita al administrado\ncomprender los elementos esenciales de la decisión y cuestionarlos de manera\ninformada. La motivación, en este contexto, debe evolucionar desde un modelo\nmeramente declarativo hacia uno verdaderamente explicativo.\n\n\n\nEn\nconsecuencia, el desafío contemporáneo no consiste en determinar si la\nadministración puede utilizar cruces automatizados de datos —facultad que se\nencuentra ampliamente reconocida—, sino en establecer bajo qué condiciones\ndicha utilización resulta compatible con un modelo de actuación administrativa\nrespetuoso de los derechos fundamentales. La respuesta a esta cuestión exige\nsuperar los parámetros clásicos de motivación y avanzar hacia estándares\ncapaces de responder a las particularidades de la fiscalización digital.\n\n\n\nEs precisamente\nen este punto donde emerge la necesidad de construir un criterio de motivación tecnológica reforzada, entendido como un estándar que obligue a la administración a\nexteriorizar no solo la conclusión de la inconsistencia detectada, sino también\nlos elementos decisivos del proceso tecnológico que la sustenta. Solo así será\nposible preservar el equilibrio entre la eficacia del control tributario y la\nvigencia de las garantías propias del Estado constitucional.\n\n\n\n<div><span style=\"background: rgb(239, 246, 255); color: rgb(37, 99, 235); padding: 2px 4px; border-radius: 3px; font-weight: bold; margin-right: 2px; margin-left: 2px; border: 1px solid rgba(37, 99, 235, 0.267);\">[Tabla 1]</span>&nbsp;<br></div>', 1, 1, NULL),
(251, 7, 'sec-69c424ab254d5', NULL, 'Implicaciones de la motivación tecnológica', '\n\nLa\nconsolidación de un estándar de motivación tecnológica reforzada no está exenta\nde desafíos prácticos y conceptuales que deben ser abordados con rigor crítico.\nSi bien la propuesta responde a una exigencia garantista frente a la\nautomatización decisional, su implementación efectiva enfrenta limitaciones\ninstitucionales, técnicas y normativas que no pueden ser ignoradas.\n\n\n\nEn primer\nlugar, la exigencia de mayores niveles de explicabilidad puede generar\ntensiones operativas en administraciones tributarias que han incorporado\nsistemas tecnológicos sin prever mecanismos de auditabilidad jurídica desde su\ndiseño. Muchos modelos algorítmicos utilizados para la detección de riesgos\nfiscales priorizan eficiencia predictiva sobre transparencia estructural, lo\nque dificulta traducir su lógica interna en términos jurídicamente\ncomprensibles. Exigir motivaciones cualificadas en estos contextos puede\nrevelar déficits estructurales en la arquitectura tecnológica estatal.\n\n\n\nEn segundo\ntérmino, la obligación de exteriorizar elementos relevantes del proceso\nautomatizado puede entrar en conflicto con la protección del secreto fiscal o\ncon la preservación de estrategias de control destinadas a prevenir la evasión.\nExiste el riesgo de que una interpretación maximalista del estándar reforzado\ncomprometa herramientas legítimas de fiscalización. El desafío consiste en\ndelimitar con precisión el núcleo mínimo de información indispensable para la\ndefensa, evitando tanto la opacidad injustificada como la divulgación que\ndebilite la eficacia del sistema.\n\n\n\nAsimismo, debe\nconsiderarse el problema de la formalización aparente. La experiencia comparada\ndemuestra que la introducción de nuevas exigencias formales puede derivar en\nrespuestas burocráticas estandarizadas que simulan cumplimiento sin alterar\nsustantivamente la asimetría informativa existente. Una motivación\ntecnológicamente “reforzada” que se limite a incorporar terminología técnica\nsin ofrecer verdadera inteligibilidad reproduciría, bajo una apariencia\nsofisticada, las mismas deficiencias que pretende superar.\n\n\n\nOtro aspecto\nrelevante es la heterogeneidad institucional en la región andina. Las\ncapacidades técnicas, los marcos regulatorios y los niveles de digitalización\nno son uniformes, lo que plantea interrogantes sobre la viabilidad inmediata de\nestándares elevados de explicabilidad. La adopción de este modelo requiere no\nsolo ajustes normativos, sino también inversión en infraestructura tecnológica\ncompatible con criterios de transparencia y control.\n\n\n\nFinalmente,\ndesde una perspectiva teórica, la construcción de un debido proceso digital\nexige evitar respuestas meramente reactivas frente a la innovación tecnológica.\nEl derecho administrativo no puede limitarse a adaptar categorías tradicionales\nde forma incremental, sino que debe asumir una revisión estructural de sus\npresupuestos frente a entornos decisionales complejos. La motivación\ntecnológica reforzada constituye un paso en esa dirección, pero su\nconsolidación dependerá de un diálogo constante entre doctrina, jurisprudencia\ny diseño institucional.\n\n\n\nEstas tensiones\nno deslegitiman la propuesta; por el contrario, evidencian la necesidad de\ndesarrollarla con prudencia y precisión conceptual. Solo un enfoque equilibrado\npermitirá compatibilizar la modernización fiscal con la vigencia efectiva de\nlas garantías propias del constitucionalismo contemporáneo.\n\n\n\n\n\nLa\nfiscalización tributaria automatizada ha transformado la estructura del\nrazonamiento administrativo, introduciendo procesos decisionales\ntecnológicamente mediados que tensionan los estándares clásicos del deber de\nmotivación. Este cambio exige reinterpretar las garantías tradicionales del\ndebido proceso en entornos digitales.\n\n\n\nEl estudio ha\ndemostrado que los parámetros construidos para decisiones predominantemente\nhumanas resultan insuficientes cuando la determinación tributaria se apoya en\ncruces automatizados de datos cuya lógica no es plenamente accesible para el\ncontribuyente. En estos contextos, la mera comunicación del resultado no\nsatisface la exigencia constitucional de motivación.\n\n\n\nLa\nautomatización puede generar riesgos de opacidad decisional que afectan\nmaterialmente el derecho de defensa y dificultan el control jurisdiccional. Por\nello, se propone reconocer la motivación tecnológica reforzada como un estándar\ncualificado de justificación que incorpore criterios mínimos de explicabilidad,\ntrazabilidad y acceso a la información determinante.\n\n\n\nLa adopción de\neste estándar no obstaculiza la eficiencia recaudatoria, sino que la integra\ndentro de los márgenes del Estado constitucional. La legitimidad de la\nfiscalización digital dependerá, en última instancia, de su compatibilidad con\nun modelo de actuación transparente, controlable y respetuoso de las garantías\nprocedimentales.', 2, 1, NULL),
(594, 9, 'sec-69d5f598325f6', NULL, 'I.      Introducción', '<div style=\"text-align: left;\"><span lang=\"ES-PE\" style=\"font-family: &quot;Times New Roman&quot;, serif; font-size: 12pt; text-align: justify; text-indent: 14.2pt; line-height: 115%;\">La acelerada digitalización de las\nadministraciones tributarias ha redefinido los mecanismos tradicionales de\nfiscalización, incorporando herramientas tecnológicas capaces de procesar\ngrandes volúmenes de información y detectar inconsistencias fiscales con\nniveles de precisión inéditos. El uso de cruces automatizados de datos, modelos\npredictivos y sistemas de análisis masivo ha fortalecido la capacidad de\ncontrol estatal y </span><span lang=\"ES-PE\" style=\"font-family: &quot;Times New Roman&quot;, serif; font-size: 12pt; text-align: justify; text-indent: 14.2pt; line-height: 115%;\">ha\ncontribuido a mejorar la eficiencia recaudatoria, consolidándose como un rasgo\ncaracterístico de los sistemas tributarios contemporáneos <a href=\"#ref-9\" class=\"citation-link\" data-rid=\"ref-9\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(OECD, 2020)</a>.</span></div>\n\n<p class=\"MsoNormal\" style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\" style=\"line-height:115%\">No obstante, este proceso de modernización\ntecnológica también plantea desafíos relevantes para el derecho administrativo\ntributario, particularmente en lo que respecta a las garantías que estructuran\nel debido proceso. Entre ellas, el deber de motivación de los actos\nadministrativos adquiere una importancia renovada.&nbsp; La motivación constituye un\nelemento estructural del acto administrativo en el Estado de derecho&nbsp;<a href=\"#ref-6\" class=\"citation-link\" data-rid=\"ref-6\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(García de Enterría &amp; Fernández, 2017)</a>, en la medida en que es el principal mecanismo\na través del cual se hace visible el razonamiento de la autoridad y se\nposibilita el ejercicio efectivo del derecho de defensa del contribuyente.</span></p>\n\n<p class=\"MsoNormal\" style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\" style=\"line-height:115%\">Tradicionalmente, la motivación ha sido\nentendida como la obligación de la administración de expresar de manera clara\nlos fundamentos fácticos y jurídicos que sustentan sus decisiones. Este deber\nno solo permite controlar la arbitrariedad del poder público, sino que también\nopera como condición de legitimidad de la actuación administrativa. Sin\nembargo, los parámetros clásicos desde los cuales se ha construido esta\ngarantía responden a un modelo decisional predominantemente humano, en el que\nla reconstrucción lógica del acto resulta accesible a partir de la exposición\nargumentativa del funcionario.</span></p>\n\n<p class=\"MsoNormal\" style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\" style=\"line-height:115%\">La progresiva automatización de los\nprocesos de fiscalización introduce, en este escenario, un elemento de\ncomplejidad adicional. Cuando la detección de riesgos fiscales se apoya en\nsistemas tecnológicos cuya lógica operativa no es plenamente transparente para\nel administrado, la motivación corre el riesgo de transformarse en una\nexplicación meramente formal, incapaz de revelar los criterios sustantivos que\ncondujeron a la decisión administrativa. Esta situación puede generar\nescenarios de opacidad incompatibles con los estándares garantistas propios del\nEstado constitucional, en la medida en que las garantías procedimentales\ncumplen la función de racionalizar el ejercicio del poder público como\nexigencia estructural del constitucionalismo contemporáneo <a href=\"#ref-5\" class=\"citation-link\" data-rid=\"ref-5\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(Ferrajoli, 2011)</a>.</span></p>\n\n<p class=\"MsoNormal\" style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\" style=\"line-height:115%\">El problema adquiere especial relevancia en\nel contexto de la región andina, donde las administraciones tributarias han\navanzado de manera sostenida en la incorporación de herramientas digitales como\nparte de sus estrategias de control y determinación. Si bien estos procesos\nrepresentan una oportunidad para fortalecer la gestión fiscal, también obligan\na repensar las categorías jurídicas tradicionales con el fin de evitar que la\ninnovación tecnológica derive en restricciones indirectas al ejercicio de los derechos\nde los contribuyentes.</span></p>\n\n<p class=\"MsoNormal\" style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\" style=\"line-height:115%\">En este contexto, surge una cuestión\ncentral para el derecho tributario contemporáneo: ¿resultan suficientes los\nestándares tradicionales de motivación para garantizar el derecho de defensa\ncuando las decisiones tributarias se sustentan en procesos automatizados de\nanálisis de datos? Interrogar esta premisa implica reconocer que la\ntransformación tecnológica no solo modifica las formas de actuación\nadministrativa, sino que también tensiona los marcos conceptuales desde los\ncuales se han protegido históricamente las garantías procedimentales.</span></p>\n\n<p class=\"MsoNormal\" style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\" style=\"line-height:115%\">El presente estudio se desarrolla a partir\nde un enfoque jurídico-dogmático de carácter analítico, basado en la revisión\ncrítica de la doctrina del derecho administrativo, la teoría del debido proceso\ny los aportes contemporáneos sobre decisiones tecnológicamente mediadas. A\ntravés de este método, se examinan las tensiones que surgen entre la\nfiscalización tributaria automatizada y las garantías procedimentales clásicas,\ncon el propósito de formular una propuesta conceptual que contribuya al\nfortalecimiento de la motivación administrativa en entornos digitales,\nparticularmente en el contexto de la región andina.</span></p>\n\n<p class=\"MsoNormal\" style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\" style=\"line-height:115%\">El artículo tiene como objetivo analizar el\nimpacto de la fiscalización tributaria automatizada en la configuración actual\ndel deber de motivación administrativa y examinar la necesidad de reconocer la\nmotivación tecnológica como un estándar orientado a reforzar la protección del\nderecho de defensa. Se sostiene que la insuficiencia motivacional en entornos\ndigitalizados puede limitar materialmente la capacidad de contradicción del\ncontribuyente y debilitar el control jurídico de la actuación administrativa.</span></p>\n\n<p class=\"MsoNormal\" style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\" style=\"line-height:115%\">Como principal aporte, el trabajo propone\ncomprender la motivación tecnológica no solo como una adaptación instrumental\ndel deber de motivar, sino como una exigencia cualificada de justificación que\npermita compatibilizar la eficiencia del control fiscal con el respeto\nirrestricto de las garantías procedimentales. Desde esta perspectiva, la\ntransparencia decisional se presenta como una condición indispensable para\npreservar la legitimidad del poder tributario en escenarios de creciente\nautomatización.</span></p>\n\n<p class=\"MsoNormal\" style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\" style=\"line-height:115%\">Para el desarrollo de esta propuesta, el\nartículo se estructura en cuatro apartados. En primer lugar, se examina la\nevolución del deber de motivación en el derecho administrativo y su función\ncomo garantía frente al ejercicio del poder público. En segundo término, se\nanalizan los desafíos que la fiscalización automatizada plantea para los\nestándares tradicionales de fundamentación. Posteriormente, se formula la\nnoción de motivación tecnológica como categoría conceptual necesaria para el\nderecho administrativo tributario contemporáneo. En el cuarto apartado, se\nabordan sus implicaciones para el derecho de defensa y el control\njurisdiccional, incorporando además una reflexión crítica sobre los límites y\ndesafíos de implementación de este estándar en la región andina. Finalmente, se\nsintetizan y se formulan las conclusiones del estudio.</span></p><br>', 1, 1, NULL),
(595, 9, 'sec-69d5f59832e4f', NULL, 'II.  DESARROLLO', '', 2, 1, NULL),
(596, 9, 'sec-69d5f59833028', NULL, '2.1. La motivación administrativa como garantía del debido proceso tributario', '<p style=\"margin-top:0cm;text-align:justify;line-height:115%\"><span lang=\"ES-PE\">El\ndeber de motivación de los actos administrativos constituye uno de los pilares\nestructurales del Estado constitucional de derecho, en tanto opera como un\nmecanismo de racionalización del poder público y como una garantía frente a\neventuales actuaciones arbitrarias de la administración. En materia tributaria,\nesta exigencia adquiere una relevancia particular debido a la posición de\nsupremacía jurídica que ejerce la administración frente al contribuyente y al\nimpacto directo que sus decisiones pueden generar sobre la esfera patrimonial\nde los administrados.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La motivación\nno debe entenderse únicamente como una formalidad destinada a justificar la\ndecisión adoptada, sino como una condición material de validez del acto\nadministrativo&nbsp;<a href=\"#ref-11\" class=\"citation-link\" data-rid=\"ref-11\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(Santamaría Pastor, 2018)</a>.<a href=\"#_ftn1\" name=\"_ftnref1\" title=\"\"><span class=\"MsoFootnoteReference\"><span class=\"MsoFootnoteReference\"><span lang=\"ES-PE\" style=\"font-size:12.0pt;line-height:200%;font-family:&quot;Times New Roman&quot;,serif\"><sup></sup></span></span></span></a><a href=\"#fn-1\" class=\"fn-link\" data-fnid=\"fn-1\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-1\" class=\"fn-link\" data-fnid=\"fn-1\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-1\" class=\"fn-link\" data-fnid=\"fn-1\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-1\" class=\"fn-link\" data-fnid=\"fn-1\" style=\"color:#d97706; text-decoration:none;\">[1]</a></span>\nSu función principal consiste en exteriorizar el razonamiento que conecta los\nhechos verificados con la norma aplicada, permitiendo así comprender el <em>iter</em>\nlógico-jurídico seguido por la autoridad. De este modo, la motivación cumple\nuna triple función: legitima el ejercicio de la potestad administrativa,\nposibilita el ejercicio efectivo del derecho de defensa y facilita el control\njurisdiccional posterior&nbsp;<a href=\"#ref-1\" class=\"citation-link\" data-rid=\"ref-1\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(Alexy, 2002)</a>.<a href=\"#_ftn2\" name=\"_ftnref2\" title=\"\"><span class=\"MsoFootnoteReference\"><span class=\"MsoFootnoteReference\"><span lang=\"ES-PE\" style=\"font-size:12.0pt;line-height:200%;font-family:&quot;Times New Roman&quot;,serif\"><sup></sup></span></span></span></a><a href=\"#fn-2\" class=\"fn-link\" data-fnid=\"fn-2\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-2\" class=\"fn-link\" data-fnid=\"fn-2\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-2\" class=\"fn-link\" data-fnid=\"fn-2\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-2\" class=\"fn-link\" data-fnid=\"fn-2\" style=\"color:#d97706; text-decoration:none;\">[2]</a></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Desde una\nperspectiva garantista, la ausencia o insuficiencia de motivación no solo\nconstituye un defecto estructural del acto, sino que puede generar su nulidad\npor vulneración del debido proceso&nbsp;<a href=\"#ref-6\" class=\"citation-link\" data-rid=\"ref-6\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(García de Enterría &amp; Fernández, 2017)</a>.<a href=\"#_ftn3\" name=\"_ftnref3\" title=\"\"><span class=\"MsoFootnoteReference\"><span class=\"MsoFootnoteReference\"><span lang=\"ES-PE\" style=\"font-size:12.0pt;line-height:\n200%;font-family:&quot;Times New Roman&quot;,serif\"><sup></sup></span></span></span></a><a href=\"#fn-3\" class=\"fn-link\" data-fnid=\"fn-3\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-3\" class=\"fn-link\" data-fnid=\"fn-3\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-3\" class=\"fn-link\" data-fnid=\"fn-3\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-3\" class=\"fn-link\" data-fnid=\"fn-3\" style=\"color:#d97706; text-decoration:none;\">[3]</a></span> En\nefecto, cuando el contribuyente desconoce las razones concretas que sustentan\nuna determinación tributaria, se ve materialmente limitado para cuestionar la\ndecisión, ofrecer prueba pertinente o controvertir los criterios utilizados por\nla administración. La motivación, por tanto, no se agota en explicar el “qué”\nde la decisión, sino que debe revelar el “por qué” y el “cómo” de la misma.</p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La doctrina\nadministrativa contemporánea coincide en señalar que la motivación adecuada\nexige algo más que la mera cita de disposiciones normativas o la reproducción\nde fórmulas estandarizadas. Supone, por el contrario, una justificación\nindividualizada que atienda a las particularidades del caso concreto y que\nevidencie una verdadera actividad valorativa por parte de la autoridad. Este\nestándar resulta especialmente relevante en el ámbito tributario, donde la\ncomplejidad técnica de las determinaciones puede generar asimetrías\ninformativas significativas entre la administración y el contribuyente&nbsp;<a href=\"#ref-7\" class=\"citation-link\" data-rid=\"ref-7\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(Hildebrandt, 2015)</a>.<a href=\"#_ftn4\" name=\"_ftnref4\" title=\"\"><span class=\"MsoFootnoteReference\"><span class=\"MsoFootnoteReference\"><span lang=\"ES-PE\" style=\"font-size:12.0pt;line-height:200%;font-family:&quot;Times New Roman&quot;,serif\"><sup></sup></span></span></span></a><a href=\"#fn-4\" class=\"fn-link\" data-fnid=\"fn-4\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-4\" class=\"fn-link\" data-fnid=\"fn-4\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-4\" class=\"fn-link\" data-fnid=\"fn-4\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-4\" class=\"fn-link\" data-fnid=\"fn-4\" style=\"color:#d97706; text-decoration:none;\">[4]</a></span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">A ello se suma\nque la motivación cumple una función preventiva frente a la arbitrariedad. La\nobligación de explicar las razones de la decisión impone a la administración un\nejercicio de autocontrol que reduce el margen de discrecionalidad y favorece la\nadopción de decisiones fundadas en criterios jurídicamente verificables. En\neste sentido, la motivación actúa como un puente entre el principio de\nlegalidad y el derecho a la tutela administrativa efectiva.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">No obstante,\nlos estándares tradicionales de motivación han sido construidos sobre la base\nde un modelo decisional predominantemente humano, en el que la autoridad\nadministrativa analiza los hechos, interpreta la norma y formula una conclusión\nque puede ser reconstruida a partir del texto del acto. Este paradigma comienza\na tensionarse en contextos donde la determinación tributaria se apoya en\nherramientas tecnológicas capaces de procesar grandes volúmenes de información\ny generar alertas o perfiles de riesgo mediante operaciones algorítmicas.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La cuestión que\nemerge, entonces, no es la vigencia del deber de motivación —cuya centralidad\npermanece incuestionable—, sino la suficiencia de sus parámetros clásicos\nfrente a nuevas formas de producción de la decisión administrativa. Si la\nmotivación tiene como finalidad hacer comprensible el razonamiento decisorio,\nresulta legítimo preguntarse hasta qué punto dicha finalidad puede cumplirse\ncuando parte de ese razonamiento se encuentra mediado por sistemas tecnológicos\ncuya lógica no siempre es accesible para el administrado.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Este escenario\nobliga a replantear el alcance del deber de motivación en clave evolutiva. La\ngarantía no puede permanecer estática frente a transformaciones profundas en la\nforma en que la administración construye sus decisiones. Por el contrario, debe\nadaptarse para seguir cumpliendo su función protectora, evitando que la\nincorporación de tecnología derive en espacios de opacidad incompatibles con un\nmodelo garantista de actuación administrativa.</span></p>\n\n<h1><strong><span lang=\"ES-TRAD\"></span></strong></h1><br>', 3, 2, NULL),
(597, 9, 'sec-69d5f59833280', NULL, '2.2. Cruces automatizados de datos y riesgo de opacidad decisional en la fiscalización tributaria', '<p style=\"margin-top:0cm;text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La\ntransformación digital de las administraciones tributarias ha introducido\nnuevas herramientas de control orientadas a optimizar la detección de\nincumplimientos fiscales. Entre ellas, los cruces automatizados de datos se han\nconsolidado como un instrumento estratégico para identificar inconsistencias\nentre la información declarada por los contribuyentes y aquella proveniente de\nterceros, registros financieros o bases de datos estatales. Este modelo de\nfiscalización preventiva permite ampliar la capacidad de supervisión de la\nadministración y mejorar los niveles de eficiencia recaudatoria.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Sin embargo, la\ncreciente dependencia de estos mecanismos tecnológicos también plantea desafíos\nrelevantes desde la perspectiva del derecho administrativo y tributario. Uno de\nlos más significativos es el riesgo de </span><span lang=\"ES-PE\">opacidad decisional</span><span lang=\"ES-PE\">,\nentendido como la dificultad del administrado para comprender los elementos\ndeterminantes que condujeron a la emisión del acto administrativo cuando estos\nse encuentran asociados a procesos automatizados de tratamiento de información.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En muchos\ncasos, la motivación de los actos derivados de cruces de datos se limita a\nseñalar la existencia de diferencias o inconsistencias detectadas por los\nsistemas informáticos, sin detallar aspectos esenciales como la fuente\nespecífica de los datos utilizados, los criterios de selección aplicados, la\nrelevancia atribuida a determinadas variables o el margen de error inherente al\nprocesamiento automatizado. Esta forma de motivación, aunque formalmente\nexistente, puede resultar materialmente insuficiente para garantizar una\ncontradicción efectiva&nbsp;<a href=\"#ref-4\" class=\"citation-link\" data-rid=\"ref-4\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(Citron, 2008)</a>.<a href=\"#_ftn5\" name=\"_ftnref5\" title=\"\"><span class=\"MsoFootnoteReference\"><span class=\"MsoFootnoteReference\"><span lang=\"ES-PE\" style=\"font-size:12.0pt;line-height:200%;font-family:&quot;Times New Roman&quot;,serif\"><sup></sup></span></span></span></a><a href=\"#fn-5\" class=\"fn-link\" data-fnid=\"fn-5\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-5\" class=\"fn-link\" data-fnid=\"fn-5\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-5\" class=\"fn-link\" data-fnid=\"fn-5\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-5\" class=\"fn-link\" data-fnid=\"fn-5\" style=\"color:#d97706; text-decoration:none;\">[5]</a></span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">El problema no\nradica en el uso de tecnología —cuya incorporación resulta inevitable en\nadministraciones tributarias modernas—, sino en la posibilidad de que dicha\ntecnología opere como una “caja negra” que dificulte la reconstrucción del\nrazonamiento decisorio&nbsp;<a href=\"#ref-10\" class=\"citation-link\" data-rid=\"ref-10\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(Pasquale, 2015)</a>.<a href=\"#_ftn6\" name=\"_ftnref6\" title=\"\"><span class=\"MsoFootnoteReference\"><span class=\"MsoFootnoteReference\"><span lang=\"ES-PE\" style=\"font-size:12.0pt;line-height:200%;font-family:&quot;Times New Roman&quot;,serif\"><sup></sup></span></span></span></a><a href=\"#fn-6\" class=\"fn-link\" data-fnid=\"fn-6\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-6\" class=\"fn-link\" data-fnid=\"fn-6\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-6\" class=\"fn-link\" data-fnid=\"fn-6\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-6\" class=\"fn-link\" data-fnid=\"fn-6\" style=\"color:#d97706; text-decoration:none;\">[6]</a></span>\nCuando el contribuyente no puede identificar con claridad el origen de la\ninconsistencia atribuida ni los parámetros utilizados para detectarla, su\ncapacidad de defensa se ve sustancialmente restringida.</p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">A ello se añade\nuna asimetría estructural de información. Mientras la administración dispone de\nacceso pleno a los sistemas y a la lógica de procesamiento, el contribuyente\nsolo conoce el resultado final de la operación tecnológica. Esta brecha\ninformativa puede traducirse en un desequilibrio procesal incompatible con los\nestándares contemporáneos del debido proceso administrativo.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Desde una\nperspectiva garantista, la automatización no debería implicar una reducción de\nlas exigencias de motivación, sino, por el contrario, un reforzamiento de las\nmismas. Cuanto mayor sea la complejidad técnica del proceso decisional, mayor\ndebe ser el esfuerzo de la administración por hacerlo comprensible y\nverificable. La transparencia tecnológica se convierte así en una extensión\nnatural del deber de motivación.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Este\nplanteamiento no supone exigir la revelación íntegra de algoritmos o sistemas\ncuya divulgación pudiera comprometer funciones de control fiscal. Implica, más\nbien, garantizar un nivel mínimo de explicabilidad que permita al administrado\ncomprender los elementos esenciales de la decisión y cuestionarlos de manera\ninformada. La motivación, en este contexto, debe evolucionar desde un modelo\nmeramente declarativo hacia uno verdaderamente explicativo.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En\nconsecuencia, el desafío contemporáneo no consiste en determinar si la\nadministración puede utilizar cruces automatizados de datos —facultad que se\nencuentra ampliamente reconocida—, sino en establecer bajo qué condiciones\ndicha utilización resulta compatible con un modelo de actuación administrativa\nrespetuoso de los derechos fundamentales. La respuesta a esta cuestión exige\nsuperar los parámetros clásicos de motivación y avanzar hacia estándares\ncapaces de responder a las particularidades de la fiscalización digital.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Es precisamente\nen este punto donde emerge la necesidad de construir un criterio de </span><span lang=\"ES-PE\">motivación tecnológica reforzada</span><span lang=\"ES-PE\">, entendido como un estándar que obligue a la administración a\nexteriorizar no solo la conclusión de la inconsistencia detectada, sino también\nlos elementos decisivos del proceso tecnológico que la sustenta. Solo así será\nposible preservar el equilibrio entre la eficacia del control tributario y la\nvigencia de las garantías propias del Estado constitucional.</span></p><br>', 4, 2, NULL);
INSERT INTO `article_sections` (`id`, `article_id`, `section_id`, `section_type`, `title`, `content`, `section_order`, `level`, `parent_section_id`) VALUES
(598, 9, 'sec-69d5f598334bc', NULL, '3. La motivación tecnológica reforzada como nuevo estándar garantista en la fiscalización tributaria digital', '<p style=\"margin-top:0cm;text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La\ncreciente incorporación de herramientas tecnológicas en la actividad\nfiscalizadora de las administraciones tributarias obliga a replantear los\nparámetros tradicionales desde los cuales se ha concebido el deber de\nmotivación de los actos administrativos. Si bien este deber ha sido\nhistóricamente entendido como una garantía esencial del debido proceso, su\nconfiguración clásica responde a un modelo decisional predominantemente humano,\ncaracterizado por la posibilidad de reconstruir el razonamiento jurídico a\npartir de la exposición de hechos, normas y conclusiones contenidas en el acto.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Sin embargo,\ncuando la determinación tributaria se apoya en cruces automatizados de datos,\nsistemas predictivos o procesos de analítica masiva, la estructura misma del\nrazonamiento administrativo experimenta una transformación significativa. La\ndecisión deja de ser el resultado exclusivo de una valoración directa por parte\ndel funcionario para convertirse, al menos parcialmente, en el producto de\noperaciones tecnológicas cuya lógica interna no siempre es evidente ni\naccesible para el administrado. Este desplazamiento plantea un desafío central\npara el derecho administrativo contemporáneo: garantizar que la innovación\ntecnológica no erosione las condiciones mínimas de transparencia que legitiman\nel ejercicio del poder público.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En este\ncontexto, sostener que basta con aplicar los estándares tradicionales de\nmotivación implica desconocer la magnitud del cambio que introduce la\nautomatización en los procesos de toma de decisiones. La mera indicación de que\nuna inconsistencia ha sido “detectada por los sistemas informáticos de la\nadministración” difícilmente satisface la exigencia de comprensibilidad que\nsubyace al deber de motivar. Por el contrario, puede generar una apariencia de\nfundamentación que, en términos materiales, oculte los elementos decisivos del\nacto administrativo.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Frente a este\nescenario, resulta necesario avanzar hacia la construcción de un estándar de </span><strong><span lang=\"ES-PE\" style=\"font-weight:normal\">motivación tecnológica reforzada</span></strong><span lang=\"ES-PE\">, entendido como una exigencia cualificada de justificación\naplicable a aquellos actos administrativos cuya génesis se encuentra mediada\npor procesos automatizados de tratamiento de información. Este estándar no\nsupone una ruptura con la teoría clásica de la motivación, sino su evolución\nnatural frente a entornos decisionales tecnológicamente complejos.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La motivación\ntecnológica reforzada parte de una premisa fundamental: </span><strong><span lang=\"ES-PE\" style=\"font-weight:normal\">a mayor complejidad técnica del proceso\ndecisional, mayor debe ser el deber de explicabilidad de la administración</span></strong><span lang=\"ES-PE\">. Este principio responde a una lógica garantista orientada a\npreservar el equilibrio entre la eficacia del control tributario y la\nprotección de los derechos fundamentales del contribuyente.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Desde esta\nperspectiva, la motivación de los actos tributarios basados en cruces\nautomatizados de datos debería incorporar, al menos, ciertos elementos\nestructurales que permitan reconstruir el razonamiento decisorio sin exigir la\nrevelación íntegra de los sistemas tecnológicos utilizados.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En primer\nlugar, se requiere la </span><strong><span lang=\"ES-PE\" style=\"font-weight:normal\">identificación\nclara del origen de la información</span></strong><span lang=\"ES-PE\"> que dio\nlugar a la actuación administrativa. El contribuyente debe poder conocer si los\ndatos provienen de declaraciones propias, reportes de terceros, registros\nfinancieros o bases estatales interconectadas. Sin esta precisión, la\nposibilidad de cuestionar la veracidad o pertinencia de la información se ve\nsustancialmente limitada.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En segundo\ntérmino, la motivación debe explicitar los </span><strong><span lang=\"ES-PE\" style=\"font-weight:normal\">criterios de relevancia aplicados por la\nadministración</span></strong><b><span lang=\"ES-PE\"> </span></b><span lang=\"ES-PE\">para\nconsiderar que la inconsistencia detectada justifica la emisión del acto. No\ntoda divergencia de datos posee necesariamente la misma entidad jurídica, por\nlo que resulta indispensable comprender por qué determinados hallazgos fueron\ninterpretados como indicios suficientes de incumplimiento.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Un tercer\ncomponente esencial es la trazabilidad mínima del proceso de análisis. Esto no\nimplica revelar algoritmos protegidos ni comprometer estrategias de control\nfiscal, sino ofrecer información suficiente para entender cómo se pasó del dato\nbruto a la conclusión administrativa. La trazabilidad opera aquí como una\ncondición de auditabilidad del acto&nbsp;<a href=\"#ref-8\" class=\"citation-link\" data-rid=\"ref-8\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(Kroll et al., 2017)</a>.<a href=\"#_ftn7\" name=\"_ftnref7\" title=\"\"><span class=\"MsoFootnoteReference\"><span class=\"MsoFootnoteReference\"><span lang=\"ES-PE\" style=\"font-size:12.0pt;line-height:\n200%;font-family:&quot;Times New Roman&quot;,serif\"><sup></sup></span></span></span></a><a href=\"#fn-7\" class=\"fn-link\" data-fnid=\"fn-7\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-7\" class=\"fn-link\" data-fnid=\"fn-7\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-7\" class=\"fn-link\" data-fnid=\"fn-7\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-7\" class=\"fn-link\" data-fnid=\"fn-7\" style=\"color:#d97706; text-decoration:none;\">[7]</a></span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Asimismo, la\nmotivación tecnológica reforzada exige garantizar el </span><strong><span lang=\"ES-PE\" style=\"font-weight:normal\">acceso efectivo del contribuyente a los\nelementos determinantes de la decisión</span></strong><span lang=\"ES-PE\">, de\nmodo que pueda ejercer su derecho de defensa en condiciones de relativa\nigualdad informativa. La opacidad tecnológica no puede convertirse en un\nobstáculo estructural para la contradicción.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Finalmente,\neste estándar demanda que la administración explicite el </span><strong><span lang=\"ES-PE\" style=\"font-weight:normal\">grado de intervención humana en la\ndecisión</span></strong><b><span lang=\"ES-PE\">. </span></b><span lang=\"ES-PE\">Determinar\nsi el acto responde a un procesamiento completamente automatizado o a una\nvalidación posterior por parte de un funcionario no es una cuestión menor, pues\nincide directamente en las posibilidades de revisión y en la atribución de\nresponsabilidad administrativa.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La adopción de\nestos parámetros no solo fortalece la posición jurídica del contribuyente, sino\nque también contribuye a la legitimidad institucional de la administración\ntributaria. En contextos de creciente digitalización, la confianza en las\ndecisiones públicas depende en buena medida de la percepción de transparencia\nque estas proyectan.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Conviene\nsubrayar que la motivación tecnológica reforzada no pretende obstaculizar la\nmodernización de la fiscalización tributaria ni imponer cargas\ndesproporcionadas a la administración. Su finalidad es evitar que la eficiencia\noperativa se traduzca en espacios de inmunidad frente al control jurídico. La\ntecnología debe integrarse al Estado de derecho, no operar al margen de sus\ngarantías.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Desde una\nperspectiva más amplia, este estándar se inscribe en la progresiva\nconsolidación de un </span><strong><span lang=\"ES-PE\" style=\"font-weight:normal\">debido\nproceso digital</span></strong><span lang=\"ES-PE\">, entendido como la adaptación\nde las garantías procedimentales clásicas a entornos caracterizados por el uso\nintensivo de tecnologías de información. Así como el derecho administrativo ha\ndebido ajustarse históricamente a distintas transformaciones organizativas y\ntécnicas, hoy enfrenta el reto de responder normativamente a la automatización.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En\nconsecuencia, la motivación tecnológica reforzada emerge como una categoría\nnecesaria para el derecho administrativo tributario contemporáneo. Su\nreconocimiento permite superar el falso dilema entre eficiencia recaudatoria y\nprotección de derechos, demostrando que ambas dimensiones no solo son\ncompatibles, sino recíprocamente dependientes en un modelo de actuación\nadministrativa verdaderamente garantista&nbsp;<a href=\"#ref-3\" class=\"citation-link\" data-rid=\"ref-3\" style=\"color:#2563eb; text-decoration:none; border-bottom:1px dotted #2563eb;\">(Brownsword, 2019)</a>.<a href=\"#_ftn8\" name=\"_ftnref8\" title=\"\"><span class=\"MsoFootnoteReference\"><span class=\"MsoFootnoteReference\"><span lang=\"ES-PE\" style=\"font-size:12.0pt;line-height:\n200%;font-family:&quot;Times New Roman&quot;,serif\"><sup></sup></span></span></span></a><a href=\"#fn-8\" class=\"fn-link\" data-fnid=\"fn-8\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-8\" class=\"fn-link\" data-fnid=\"fn-8\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-8\" class=\"fn-link\" data-fnid=\"fn-8\" style=\"color:#d97706; text-decoration:none;\"><sup></sup></a><a href=\"#fn-8\" class=\"fn-link\" data-fnid=\"fn-8\" style=\"color:#d97706; text-decoration:none;\">[8]</a></span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">El desafío\nfuturo no radica en determinar si las administraciones tributarias deben\nutilizar herramientas tecnológicas —cuestión prácticamente resuelta en sistemas\nfiscales modernos—, sino en establecer los límites jurídicos que aseguren que\ndicha utilización se mantenga dentro de los márgenes del Estado constitucional.\nSolo a través de decisiones suficientemente motivadas será posible preservar la\nracionalidad del poder tributario en escenarios de creciente automatización.</span></p><br>', 5, 1, NULL),
(599, 9, 'sec-69d5f5983372f', NULL, '4. Implicaciones de la motivación tecnológica reforzada para el derecho de defensa y el control jurisdiccional', '<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La construcción\nde un estándar de motivación tecnológica reforzada no constituye únicamente un\najuste teórico dentro del derecho administrativo tributario, sino que proyecta\nefectos directos sobre dos garantías estructurales del Estado constitucional:\nel derecho de defensa del contribuyente y la posibilidad de un control\njurisdiccional efectivo de la actuación administrativa. Ambos elementos\nresultan indisociables de la idea misma de debido proceso y adquieren nuevas\ndimensiones en escenarios caracterizados por la creciente automatización de las\ndecisiones públicas.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">El derecho de\ndefensa presupone, como condición mínima, el conocimiento suficiente de los\nfundamentos del acto administrativo. Sin embargo, cuando la motivación se\nlimita a enunciar resultados derivados de sistemas automatizados sin ofrecer\nuna explicación inteligible del proceso que los generó, el administrado\nenfrenta una barrera epistémica que restringe materialmente su capacidad de\ncontradicción. No se trata únicamente de un problema de acceso a la\ninformación, sino de la imposibilidad práctica de comprender el razonamiento\ndecisorio que debe ser controvertido.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Esta situación\npuede derivar en una forma particularmente sofisticada de indefensión: aquella\nque no surge de la ausencia formal de motivación, sino de su insuficiencia\ncualitativa frente a entornos tecnológicos complejos. En tales casos, el\ncontribuyente conoce la conclusión administrativa, pero no dispone de los\nelementos necesarios para evaluar su corrección, identificar posibles errores o\ncuestionar la pertinencia de los datos utilizados. El derecho de defensa corre\nasí el riesgo de convertirse en una garantía meramente declarativa.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Desde esta\nperspectiva, la motivación tecnológica reforzada actúa como un mecanismo de\nequilibrio frente a la asimetría informativa que caracteriza la relación entre\nla administración tributaria y el contribuyente en contextos de fiscalización\ndigital. Al exigir un nivel mínimo de explicabilidad, este estándar no solo\nfacilita la comprensión del acto, sino que restituye condiciones básicas de\nigualdad procesal, permitiendo que la controversia se desarrolle sobre bases\ncognitivas razonablemente compartidas.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Las\nimplicaciones de este modelo también se proyectan sobre el ámbito\njurisdiccional. El control judicial de los actos administrativos depende, en\ngran medida, de la posibilidad de reconstruir el iter lógico que condujo a la\ndecisión. Cuando la motivación resulta opaca o incompleta, el juez se enfrenta\na serias dificultades para evaluar la razonabilidad del acto, verificar la\ncorrecta valoración de los hechos o determinar la proporcionalidad de la medida\nadoptada.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En este\nsentido, la insuficiencia motivacional no solo afecta al contribuyente, sino\nque compromete la propia función revisora de los órganos jurisdiccionales. Sin\nuna explicación adecuada del proceso decisional, el control judicial corre el\nriesgo de desplazarse desde un examen sustantivo hacia una revisión meramente\nformal, debilitando uno de los principales contrapesos del poder\nadministrativo.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Debe\nadvertirse, además, que la automatización introduce nuevas complejidades\nprobatorias. La tradicional lógica documental del procedimiento administrativo\nse ve progresivamente reemplazada por entornos digitales en los que la\nevidencia puede estar compuesta por registros electrónicos, modelos de riesgo o\npatrones detectados mediante procesamiento algorítmico. Este cambio obliga a\nrepensar las categorías clásicas de la prueba administrativa y a reconocer la\nnecesidad de garantizar condiciones mínimas de auditabilidad tecnológica.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La exigencia de\ntrazabilidad —como componente de la motivación tecnológica reforzada— adquiere\naquí una relevancia decisiva. Solo a través de mecanismos que permitan rastrear\nel origen y tratamiento de la información será posible someter estos elementos a\ncontradicción y valoración judicial. La trazabilidad no debe entenderse como\nuna carga desproporcionada para la administración, sino como una condición\ninherente a la legitimidad de decisiones cada vez más dependientes de\ninfraestructuras digitales.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Otro aspecto\nparticularmente relevante es el riesgo de deferencia excesiva hacia las\ndecisiones tecnológicamente mediadas. La aparente objetividad de los sistemas\nautomatizados puede generar una presunción implícita de corrección que inhiba\ntanto la impugnación por parte del contribuyente como el escrutinio judicial\nriguroso. Frente a este fenómeno, la motivación reforzada opera como un\nrecordatorio de que ninguna decisión administrativa debe quedar sustraída del\nexamen crítico propio del Estado de derecho.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Asimismo, la\nexplicitación del grado de intervención humana en el proceso decisional permite\ndelimitar responsabilidades y evita la dilución de la autoría administrativa\nbajo la apariencia de neutralidad tecnológica. La administración no puede\nescudarse en la complejidad de los sistemas que utiliza para eludir el deber de\njustificar sus decisiones; por el contrario, el uso de tales herramientas\nintensifica la necesidad de fundamentación.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En términos más\namplios, la adopción de un estándar de motivación tecnológica reforzada\ncontribuye a consolidar un modelo de administración tributaria compatible con\nlos principios de transparencia, responsabilidad y control. Lejos de\nobstaculizar la innovación, este enfoque promueve una integración armónica\nentre tecnología y garantías jurídicas, reafirmando que la modernización del\naparato estatal debe desarrollarse dentro de los márgenes del\nconstitucionalismo contemporáneo.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La\nfiscalización digital representa, sin duda, una oportunidad para fortalecer la\ncapacidad recaudatoria y mejorar la eficiencia administrativa. No obstante, su\nlegitimidad dependerá en última instancia de la confianza que inspire en los\nadministrados. Dicha confianza no se construye únicamente a partir de\nresultados, sino también mediante procedimientos comprensibles y decisiones\nsuficientemente justificadas.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En\nconsecuencia, la motivación tecnológica reforzada no solo protege el derecho de\ndefensa y facilita el control jurisdiccional, sino que se proyecta como un\nelemento estructural para la sostenibilidad institucional de las\nadministraciones tributarias en la era digital. Su consolidación permitirá\nevitar que la automatización derive en espacios de poder poco transparentes y\ncontribuirá a preservar la centralidad del debido proceso como eje ordenador de\nla actuación administrativa.</span></p><br>', 6, 1, NULL),
(600, 9, 'sec-69d5f598339dc', NULL, '4.1. Tensiones y desafíos de implementación en la región andina', '<p style=\"margin-top:0cm;text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La\nconsolidación de un estándar de motivación tecnológica reforzada no está exenta\nde desafíos prácticos y conceptuales que deben ser abordados con rigor crítico.\nSi bien la propuesta responde a una exigencia garantista frente a la\nautomatización decisional, su implementación efectiva enfrenta limitaciones\ninstitucionales, técnicas y normativas que no pueden ser ignoradas.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En primer\nlugar, la exigencia de mayores niveles de explicabilidad puede generar\ntensiones operativas en administraciones tributarias que han incorporado\nsistemas tecnológicos sin prever mecanismos de auditabilidad jurídica desde su\ndiseño. Muchos modelos algorítmicos utilizados para la detección de riesgos\nfiscales priorizan eficiencia predictiva sobre transparencia estructural, lo\nque dificulta traducir su lógica interna en términos jurídicamente\ncomprensibles. Exigir motivaciones cualificadas en estos contextos puede\nrevelar déficits estructurales en la arquitectura tecnológica estatal.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">En segundo\ntérmino, la obligación de exteriorizar elementos relevantes del proceso\nautomatizado puede entrar en conflicto con la protección del secreto fiscal o\ncon la preservación de estrategias de control destinadas a prevenir la evasión.\nExiste el riesgo de que una interpretación maximalista del estándar reforzado\ncomprometa herramientas legítimas de fiscalización. El desafío consiste en\ndelimitar con precisión el núcleo mínimo de información indispensable para la\ndefensa, evitando tanto la opacidad injustificada como la divulgación que\ndebilite la eficacia del sistema.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Asimismo, debe\nconsiderarse el problema de la formalización aparente. La experiencia comparada\ndemuestra que la introducción de nuevas exigencias formales puede derivar en\nrespuestas burocráticas estandarizadas que simulan cumplimiento sin alterar\nsustantivamente la asimetría informativa existente. Una motivación\ntecnológicamente “reforzada” que se limite a incorporar terminología técnica\nsin ofrecer verdadera inteligibilidad reproduciría, bajo una apariencia\nsofisticada, las mismas deficiencias que pretende superar.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Otro aspecto\nrelevante es la heterogeneidad institucional en la región andina. Las\ncapacidades técnicas, los marcos regulatorios y los niveles de digitalización\nno son uniformes, lo que plantea interrogantes sobre la viabilidad inmediata de\nestándares elevados de explicabilidad. La adopción de este modelo requiere no\nsolo ajustes normativos, sino también inversión en infraestructura tecnológica\ncompatible con criterios de transparencia y control.</span></p><p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\"><span style=\"background: rgb(240, 253, 244); color: rgb(22, 101, 52); padding: 2px 4px; border-radius: 3px; font-weight: bold; margin-right: 2px; margin-left: 2px; border: 1px solid rgba(22, 101, 52, 0.267);\">[Figura 1]</span>&nbsp;<br></span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Finalmente,\ndesde una perspectiva teórica, la construcción de un debido proceso digital\nexige evitar respuestas meramente reactivas frente a la innovación tecnológica.\nEl derecho administrativo no puede limitarse a adaptar categorías tradicionales\nde forma incremental, sino que debe asumir una revisión estructural de sus\npresupuestos frente a entornos decisionales complejos. La motivación\ntecnológica reforzada constituye un paso en esa dirección, pero su\nconsolidación dependerá de un diálogo constante entre doctrina, jurisprudencia\ny diseño institucional.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">Estas tensiones\nno deslegitiman la propuesta; por el contrario, evidencian la necesidad de\ndesarrollarla con prudencia y precisión conceptual. Solo un enfoque equilibrado\npermitirá compatibilizar la modernización fiscal con la vigencia efectiva de\nlas garantías propias del constitucionalismo contemporáneo.</span></p><p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\"><span style=\"background: rgb(239, 246, 255); color: rgb(37, 99, 235); padding: 2px 4px; border-radius: 3px; font-weight: bold; margin-right: 2px; margin-left: 2px; border: 1px solid rgba(37, 99, 235, 0.267);\">[Tabla 1]</span>&nbsp;<br></span></p>', 7, 2, NULL),
(601, 9, 'sec-69d5f59833c12', NULL, 'III. Conclusiones', '<p style=\"margin-top:0cm;text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La\nfiscalización tributaria automatizada ha transformado la estructura del\nrazonamiento administrativo, introduciendo procesos decisionales\ntecnológicamente mediados que tensionan los estándares clásicos del deber de\nmotivación. Este cambio exige reinterpretar las garantías tradicionales del\ndebido proceso en entornos digitales.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">El estudio ha\ndemostrado que los parámetros construidos para decisiones predominantemente\nhumanas resultan insuficientes cuando la determinación tributaria se apoya en\ncruces automatizados de datos cuya lógica no es plenamente accesible para el\ncontribuyente. En estos contextos, la mera comunicación del resultado no\nsatisface la exigencia constitucional de motivación.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La\nautomatización puede generar riesgos de opacidad decisional que afectan\nmaterialmente el derecho de defensa y dificultan el control jurisdiccional. Por\nello, se propone reconocer la motivación tecnológica reforzada como un estándar\ncualificado de justificación que incorpore criterios mínimos de explicabilidad,\ntrazabilidad y acceso a la información determinante.</span></p>\n\n<p style=\"text-align:justify;line-height:115%\"><span lang=\"ES-PE\">La adopción de\neste estándar no obstaculiza la eficiencia recaudatoria, sino que la integra\ndentro de los márgenes del Estado constitucional. La legitimidad de la\nfiscalización digital dependerá, en última instancia, de su compatibilidad con\nun modelo de actuación transparente, controlable y respetuoso de las garantías\nprocedimentales.</span></p><br>', 8, 1, NULL),
(602, 9, 'sec-69d5f59833df1', NULL, 'Declaración de IA generativa y tecnologías asistidas por IA en el proceso de escritura', 'En\nel desarrollo de este manuscrito se utilizaron herramientas de inteligencia\nartificial generativa exclusivamente para apoyar aspectos de revisión\ngramatical y mejora de estilo. La concepción, estructura, diseño, análisis\nconceptual, argumentación jurídica, interpretación doctrinal y conclusiones son\nresultado del trabajo intelectual original de la autora. El uso de dichas\ntecnologías no implicó generación automática de contenido sustantivo ni\nsustituyó en ningún momento el juicio crítico ni la elaboración intelectual\npropia de la investigación.<br>', 9, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `article_tables`
--

CREATE TABLE `article_tables` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `table_id` varchar(50) NOT NULL,
  `label` varchar(50) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `footer` text DEFAULT NULL,
  `html_content` longtext DEFAULT NULL,
  `table_order` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `given_names` varchar(100) NOT NULL,
  `orcid` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `affiliation` text DEFAULT NULL,
  `affiliation_id` varchar(50) DEFAULT NULL,
  `author_order` int(11) NOT NULL,
  `corresponding` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`id`, `article_id`, `surname`, `given_names`, `orcid`, `email`, `affiliation`, `affiliation_id`, `author_order`, `corresponding`) VALUES
(81, 9, 'Aguirre Bermeo', 'Andrea Catalina', 'https://orcid.org/0000-0003-3993-1999', 'acaguirre28@utpl.edu.ec', 'Universidad Técnica Particular de Loja, Ecuador', NULL, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `citations`
--

CREATE TABLE `citations` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `reference_id` int(11) NOT NULL,
  `citation_text` varchar(255) DEFAULT NULL,
  `position_in_section` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `issues`
--

CREATE TABLE `issues` (
  `id` int(11) NOT NULL,
  `volume_id` int(11) NOT NULL,
  `issue_number` varchar(20) NOT NULL,
  `publication_date` date DEFAULT NULL,
  `doi` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `issues`
--

INSERT INTO `issues` (`id`, `volume_id`, `issue_number`, `publication_date`, `doi`, `created_at`) VALUES
(1, 1, '1', NULL, NULL, '2026-03-23 00:49:04');

-- --------------------------------------------------------

--
-- Table structure for table `journals`
--

CREATE TABLE `journals` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_abbrev` varchar(100) DEFAULT NULL,
  `issn_print` varchar(20) DEFAULT NULL,
  `issn_electronic` varchar(20) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publisher_location` varchar(255) DEFAULT NULL,
  `doi_prefix` varchar(50) DEFAULT NULL,
  `base_url` varchar(255) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `language` varchar(10) DEFAULT 'es',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  `oai_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `journals`
--

INSERT INTO `journals` (`id`, `title`, `title_abbrev`, `issn_print`, `issn_electronic`, `publisher`, `publisher_location`, `doi_prefix`, `base_url`, `logo_path`, `description`, `language`, `created_at`, `updated_at`, `active`, `oai_url`) VALUES
(1, 'Revista de Derecho de la Universidad Nacional del Altiplano de Puno', 'Rev. Derecho UNAP', '2313-6944', '2707-9651', 'Facultad de Ciencias Jurídicas y Políticas de la Universidad Nacional del Altiplano de Puno', 'Puno, Perú', '10.47712', 'https://revistas.unap.edu.pe/rd/', NULL, NULL, 'es', '2026-03-22 04:29:10', '2026-04-09 06:20:35', 1, 'https://revistas.unap.edu.pe/rd/index.php/rd/oai');

-- --------------------------------------------------------

--
-- Table structure for table `journal_sections`
--

CREATE TABLE `journal_sections` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data for table `journal_sections`
--

INSERT INTO `journal_sections` (`id`, `title`) VALUES
(1, 'Teoría Crítica, Filosofía y Metodología del Derecho');

-- --------------------------------------------------------

--
-- Table structure for table `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','editor','reviewer') DEFAULT 'editor',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `journal_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `created_at`, `last_login`, `active`, `journal_id`) VALUES
(1, 'admin', 'revistaderecho@unap.edu.pe', '$2y$10$jJu85cNTaV2B.5yeIGWTbOpQ2s371bbB7274Dtn63hv8L8MdGzCqO', 'Administrador', 'admin', '2026-03-22 04:29:10', '2026-04-09 06:19:05', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `volumes`
--

CREATE TABLE `volumes` (
  `id` int(11) NOT NULL,
  `journal_id` int(11) NOT NULL,
  `volume_number` varchar(20) NOT NULL,
  `year` year(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `volumes`
--

INSERT INTO `volumes` (`id`, `journal_id`, `volume_number`, `year`, `created_at`) VALUES
(1, 1, '11', '2026', '2026-03-23 00:49:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`created_at`),
  ADD KEY `idx_article_date` (`article_id`,`created_at`);

--
-- Indexes for table `affiliations`
--
ALTER TABLE `affiliations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_article_affiliation` (`article_id`,`affiliation_id`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `article_id` (`article_id`),
  ADD KEY `issue_id` (`issue_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_article_id` (`article_id`);

--
-- Indexes for table `article_figures`
--
ALTER TABLE `article_figures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_article_figure` (`article_id`,`figure_id`),
  ADD KEY `idx_article_order` (`article_id`,`figure_order`);

--
-- Indexes for table `article_files`
--
ALTER TABLE `article_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_article_type` (`article_id`,`file_type`);

--
-- Indexes for table `article_footnotes`
--
ALTER TABLE `article_footnotes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `article_markup`
--
ALTER TABLE `article_markup`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unique_article_markup` (`article_id`),
  ADD KEY `saved_by` (`saved_by`);

--
-- Indexes for table `article_references`
--
ALTER TABLE `article_references`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_article_ref` (`article_id`,`ref_id`),
  ADD KEY `idx_article_order` (`article_id`,`reference_order`);

--
-- Indexes for table `article_sections`
--
ALTER TABLE `article_sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parent_section_id` (`parent_section_id`),
  ADD KEY `idx_article_order` (`article_id`,`section_order`);

--
-- Indexes for table `article_tables`
--
ALTER TABLE `article_tables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_article_table` (`article_id`,`table_id`),
  ADD KEY `idx_article_order` (`article_id`,`table_order`);

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_article_order` (`article_id`,`author_order`);

--
-- Indexes for table `citations`
--
ALTER TABLE `citations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `reference_id` (`reference_id`),
  ADD KEY `idx_article_section` (`article_id`,`section_id`);

--
-- Indexes for table `issues`
--
ALTER TABLE `issues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_volume_issue` (`volume_id`,`issue_number`);

--
-- Indexes for table `journals`
--
ALTER TABLE `journals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `journal_sections`
--
ALTER TABLE `journal_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`);

--
-- Indexes for table `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `volumes`
--
ALTER TABLE `volumes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_journal_volume` (`journal_id`,`volume_number`,`year`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `affiliations`
--
ALTER TABLE `affiliations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1220;

--
-- AUTO_INCREMENT for table `article_figures`
--
ALTER TABLE `article_figures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `article_files`
--
ALTER TABLE `article_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

--
-- AUTO_INCREMENT for table `article_footnotes`
--
ALTER TABLE `article_footnotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=417;

--
-- AUTO_INCREMENT for table `article_markup`
--
ALTER TABLE `article_markup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `article_references`
--
ALTER TABLE `article_references`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1033;

--
-- AUTO_INCREMENT for table `article_sections`
--
ALTER TABLE `article_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=603;

--
-- AUTO_INCREMENT for table `article_tables`
--
ALTER TABLE `article_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `citations`
--
ALTER TABLE `citations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `issues`
--
ALTER TABLE `issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `journals`
--
ALTER TABLE `journals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `journal_sections`
--
ALTER TABLE `journal_sections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1218;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `volumes`
--
ALTER TABLE `volumes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `affiliations`
--
ALTER TABLE `affiliations`
  ADD CONSTRAINT `affiliations_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `articles_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `article_figures`
--
ALTER TABLE `article_figures`
  ADD CONSTRAINT `article_figures_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_files`
--
ALTER TABLE `article_files`
  ADD CONSTRAINT `article_files_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_markup`
--
ALTER TABLE `article_markup`
  ADD CONSTRAINT `article_markup_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_markup_ibfk_2` FOREIGN KEY (`saved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `article_references`
--
ALTER TABLE `article_references`
  ADD CONSTRAINT `article_references_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_sections`
--
ALTER TABLE `article_sections`
  ADD CONSTRAINT `article_sections_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_sections_ibfk_2` FOREIGN KEY (`parent_section_id`) REFERENCES `article_sections` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `article_tables`
--
ALTER TABLE `article_tables`
  ADD CONSTRAINT `article_tables_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `authors`
--
ALTER TABLE `authors`
  ADD CONSTRAINT `authors_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `citations`
--
ALTER TABLE `citations`
  ADD CONSTRAINT `citations_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `citations_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `article_sections` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `citations_ibfk_3` FOREIGN KEY (`reference_id`) REFERENCES `article_references` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `issues`
--
ALTER TABLE `issues`
  ADD CONSTRAINT `issues_ibfk_1` FOREIGN KEY (`volume_id`) REFERENCES `volumes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `volumes`
--
ALTER TABLE `volumes`
  ADD CONSTRAINT `volumes_ibfk_1` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
