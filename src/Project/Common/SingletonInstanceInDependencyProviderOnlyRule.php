<?php

namespace ProjectArchitectureSniffer\Project\Common;

use PHPMD\AbstractNode;
use PHPMD\AbstractRule;
use PHPMD\Rule\MethodAware;

class SingletonInstanceInDependencyProviderOnlyRule extends AbstractRule implements MethodAware
{
    protected const GET_INSTANCE_METHOD_NAME = '/^(getInstance)$/';

    
    public function apply(AbstractNode $node): void
    {
        if ($this->isDependencyProvider($node)) {
            return;
        }

        foreach ($node->findChildrenOfType('MethodPostfix') as $classUsage) {
            $methodName = $classUsage->getNode()->getImage();

            if ($this->isGetInstance($methodName) === false) {
                continue;
            }

            if ($this->isStaticCall($classUsage->getNode()->getParent()->getImage()) === false) {
                continue;
            }
            
            $this->addViolation(
                $node,
                [
                    sprintf(
                        'The method %s uses ::getInstance. It can not be used outside of DependencyProvider',
                        $node->getName(),
                    ),
                ],
            );
        }
    }

    /**
     * @param \PHPMD\AbstractNode $node
     *
     * @return bool
     */
    protected function isDependencyProvider(AbstractNode $node): bool
    {
        $parent = $node->getNode()->getParent();
        $className = $parent->getNamespaceName() . '\\' . $parent->getName();


        if (preg_match('/\\\\' . '(?:Client|Yves|Glue|Zed|Service)' . '\\\\.*\\\\\w+DependencyProvider$/', $className)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $methodName
     *
     * @return bool
     */
    protected function isGetInstance(string $methodName): bool
    {
        if (preg_match(static::GET_INSTANCE_METHOD_NAME, $methodName)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $methodName
     *
     * @return bool
     */
    protected function isStaticCall(string $callSymbol): bool
    {
        if (preg_match('::', $callSymbol)) {
            return true;
        }

        return false;
    }
}
