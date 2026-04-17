CREATE TABLE tx_typo3altcha_domain_model_challenge (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) unsigned DEFAULT '0' NOT NULL,
    crdate int(11) unsigned DEFAULT '0' NOT NULL,
    hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
    challenge VARCHAR(1024) DEFAULT '' NOT NULL,
    is_solved tinyint(4) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid)
);
