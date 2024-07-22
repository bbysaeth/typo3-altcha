CREATE TABLE tx_typo3altcha_domain_model_challenge (
    uid int(11) NOT NULL auto_increment,
    challenge VARCHAR(1024) DEFAULT '' NOT NULL,
    is_solved tinyint(4) DEFAULT '0' NOT NULL,
		solution int(32) DEFAULT '0' not null,
    PRIMARY KEY (uid)
);