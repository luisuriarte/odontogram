-- openemr.form_odontogram definition
CREATE TABLE IF NOT EXISTS `form_odontogram` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `universal` varchar(10) DEFAULT NULL,
  `fdi` varchar(10) DEFAULT NULL,
  `palmer` varchar(10) DEFAULT NULL,
  `dentition_type` enum('Infant','Adult') DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `part` enum('Complete','Vertical','Distal','Mesial','Lingual','Buccal','Incisal','Occlusal') DEFAULT NULL,
  `arc` enum('Maxillary','Mandibular') DEFAULT NULL,
  `side` enum('Left','Right') DEFAULT NULL,
  `svg_id` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `svg_id_idx` (`svg_id`),
  KEY `odontogram_id_IDX` (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- openemr.form_odontogram_history definition
CREATE TABLE IF NOT EXISTS `form_odontogram_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `encounter` int(11) DEFAULT NULL,
  `user` varchar(100) DEFAULT NULL,
  `groupname` varchar(100) DEFAULT NULL,
  `authorized` tinyint(4) DEFAULT NULL,
  `activity` tinyint(4) DEFAULT 1,
  `odontogram_id` int(11) DEFAULT NULL,
  `intervention_type` enum('Diagnosis','Issue','Procedure') DEFAULT NULL,
  `list_id` varchar(100) DEFAULT NULL,
  `option_id` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `symbol` varchar(100) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `odontogram_id` (`odontogram_id`),
  KEY `list_option_idx` (`list_id`,`option_id`),
  CONSTRAINT `form_odontogram_history_ibfk_1` FOREIGN KEY (`odontogram_id`) REFERENCES `form_odontogram` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, toggle_setting_1, toggle_setting_2, activity, subtype, edit_options, `timestamp`, last_updated) VALUES('lists', 'odonto_diagnosis', 'odonto_diagnosis', 330, 1, 0.0, '', NULL, '', 0, 0, 1, '', 1, '2025-03-09 15:02:36.000', '2025-03-09 15:02:36.000');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, toggle_setting_1, toggle_setting_2, activity, subtype, edit_options, `timestamp`, last_updated) VALUES('lists', 'odonto_issue', 'odonto_issue', 332, 1, 0.0, '', NULL, '', 0, 0, 1, '', 1, '2025-03-09 15:09:38.000', '2025-03-09 15:09:38.000');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, toggle_setting_1, toggle_setting_2, activity, subtype, edit_options, `timestamp`, last_updated) VALUES('lists', 'odonto_procedures', 'odonto_procedures', 331, 1, 0.0, '', NULL, '', 0, 0, 1, '', 1, '2025-03-09 15:09:22.000', '2025-03-09 15:09:22.000');

INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, toggle_setting_1, toggle_setting_2, activity, subtype, edit_options, `timestamp`, last_updated) VALUES('odonto_diagnosis', 'initial_caries', 'Initial Caries', 1, 0, 0.0, '', 'initial_caries.svg', 'ICD10:K0251', 0, 0, 1, '', 1, '2025-03-09 15:23:13.000', '2025-03-09 15:23:13.000');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, toggle_setting_1, toggle_setting_2, activity, subtype, edit_options, `timestamp`, last_updated) VALUES('odonto_diagnosis', 'moderate_caries', 'Moderate Caries', 2, 0, 0.0, '', 'moderate_caries.svg', 'ICD10:K0261', 0, 0, 1, '', 1, '2025-03-09 15:23:13.000', '2025-03-09 15:23:13.000');
INSERT INTO openemr.list_options (list_id, option_id, title, seq, is_default, option_value, mapping, notes, codes, toggle_setting_1, toggle_setting_2, activity, subtype, edit_options, `timestamp`, last_updated) VALUES('odonto_diagnosis', 'severe_caries', 'Severe Caries', 3, 0, 0.0, '', 'severe_caries.svg', 'ICD10:K08431', 0, 0, 1, '', 1, '2025-03-09 15:23:14.000', '2025-03-09 15:23:14.000');
