<?php

return '<?php

namespace {namespace};

use NsUtil\Commands\Abstracts\Command;
                
class {classname} extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = "app:{signature}";

    /**
     * Handles the execution of the command.
     *
     * @param array $args The arguments passed to the command.
     * @return void
     */
    public function handle(array $args): void
    {
        $me = basename(str_replace(\'\\\\\', "/", self::class));
        $this->success($me);
    }
}
';
