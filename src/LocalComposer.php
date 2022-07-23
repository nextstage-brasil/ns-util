<?php

namespace NsUtil;

class LocalComposer {

    public function __construct() {
        
    }

    /**
     * Ira copiar um devido diretorio local para outro local
     * @param array $LOCAL_PROJECTS
     * @param string $src
     * @return void
     */
    public function __invoke(string $src, array $LOCAL_PROJECTS): void {
        if (!is_dir($src)) {
            die("SRC $src is not a directory");
        }
        $log = new ConsoleTable();
        $log->setHeaders(['Project', 'Status']);
        $loader = new StatusLoader(count($LOCAL_PROJECTS), 'Local Composer', 25);
        $done = 0;
        foreach ($LOCAL_PROJECTS as $projeto) {
            if (is_dir($projeto)) {
                Helper::directorySeparator($projeto);
                $dst = $projeto . "/vendor/nextstage-brasil/ns-util/src";
                $exists = is_dir($dst);
                Helper::directorySeparator($dst);

                // Remover conteudo atual
                Helper::deleteDir($dst);

                // Copiar conteudo recente
                DirectoryManipulation::recurseCopy($src, $dst);

                // Saida        
                $log->addRow([$projeto, is_dir($dst) ? ($exists ? 'Updated' : 'Created') : 'ERROR!']);
            } else {
                $log->addRow([$projeto, 'Not found']);
            }

            $done++;
            $loader->done($done);
        }
        echo PHP_EOL;
        $log->display();
    }

}
